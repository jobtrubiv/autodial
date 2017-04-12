<?php
/**
 * Модель 'DialLog'
 */
class DialLogModel extends DialLogModelAbstract
{
    /**
     * @return DialLogEntity
     */
    public function getNextHash()
    {
        $opts = $this->getCond();
        $opts->order('hash DESC');

        $dialLog = $this->getDialLog($opts);
        if (!$dialLog->exists()){
            return 1;
        }

        $hash = $dialLog->getHash();

        return ($hash + 1);
    }

    /**
     * @param int|AdminEntity $admin
     * @param int|DialRuleEntity $dialRule
     * @param FunctionResult $functionResult
     * @return FunctionResult
     */
    public function add($admin, $dialRule, $functionResult, $hash)
    {
        $result = new FunctionResult();

        $adminModel = AdminModel::getInstance();
        $dialRuleModel = DialRuleModel::getInstance();

        if (!$admin instanceof AdminEntity){
            $admin = $adminModel->getAdminByAdmin($admin);
        }

        if (!$admin->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_not_found'));
            return $result;
        }

        if (!$dialRule instanceof DialRuleEntity){
            $dialRule = $dialRuleModel->getDialRuleByDialRule($dialRule);
        }

        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        $error = null;
        $status = 'success';
        if ($functionResult->isError()){
            $status = 'failed';
            $error = $functionResult->getErrorsByString();
        }

        $data = array(
            'admin_id' => $admin->getId(),
            'dial_rule_id' => $dialRule->getId(),
            'status' => $status,
            'error' => $error,
            'hash' => $hash
        );

        $_result = $this->importDialLog($data);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_log_import_error'));
            return $result;
        }

        $result->setResult($_result->getResult());

        return $result;
    }

    /**
     * @param int|DialLogEntity $dialLog
     * @return FunctionResult
     */
    public function parseCdr($dialLog)
    {
        $result = new FunctionResult();

        $userModel = UserModel::getInstance();
        $dialLogCallModel = DialLogCallModel::getInstance();

        if (!$dialLog instanceof DialLogEntity){
            $dialLog = $this->getDialLogByDialLog($dialLog);
        }

        if (!$dialLog->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_log_not_found'));
            return $result;
        }

        $fileCdr = Zend_Registry::get('Asterisk')->cdr;
        if (!file_exists($fileCdr)){
            $result->add(TranslateModel::getTranslateMessageByCode('cdr_file_not_found'));
            return $result;
        }

        $userInCdrList = array();
        $cdrFileArray = file($fileCdr);
        foreach ($cdrFileArray as $cdr){
            $cdrArray = explode(',', $cdr);

            $accountCode = $cdrArray[0];
            $status = trim(strtolower($cdrArray[14]), '"');
            $duration = trim($cdrArray[13], '"');
            $digit = trim(str_replace('"', '', $cdrArray[17]));

            if (strpos($accountCode, $dialLog->getHash() . '_') === false){
                continue;
            }

            if (strlen($digit) > 1){
                $digit = null;
            }else{
                $digit = intval($digit);
            }

            $userPhone = trim(str_replace($dialLog->getHash() . '_', '', $accountCode));
            $userPhone = trim($userPhone, '"');

            $user = $userModel->getUserByDialRuleAndPhone($dialLog->getDialRuleId(), $userPhone);
            if (!$user->exists()){
                continue;
            }
//89507674172
            $data = array(
                'dial_log_id' => $dialLog->getId(),
                'user_id' => $user->getId(),
                'phone' => $userPhone,
                'duration' => intval($duration),
                'call_digit' => $digit,
                'status' => $status
            );

            $userInCdrList[] = $user->getId();

            $checkOpst = $dialLogCallModel->getCond();
            $checkOpst->where('user_id = ?', $user->getId());

            $check = $dialLogCallModel->getDialLogCallByDialLog($dialLog, $checkOpst);
            if (!$check->exists()){
                $dialLogCallModel->importDialLogCall($data);
            }else{
                $dialLogCallModel->updateDialLogCallByDialLogCall($check, $data);
            }
        }

        $opts = $userModel->getCond();
        $opts->where('status = ?', 'active');

        if (count($userInCdrList) > 0){
            $opts->where('id NOT IN (?)', $userInCdrList);
        }

        $userList = $userModel->getUserListByDialRule($dialLog->getDialRuleId(), $opts);
        foreach ($userList as $user){

            if ($user->isEndCall($dialLog)){
                $data = array(
                    'dial_log_id' => $dialLog->getId(),
                    'user_id' => $user->getId(),
                    'phone' => $user->getPhone(),
                    'status' => 'failed'
                );

                $checkOpst = $dialLogCallModel->getCond();
                $checkOpst->where('user_id = ?', $user->getId());

                $check = $dialLogCallModel->getDialLogCallByDialLog($dialLog, $checkOpst);
                if (!$check->exists()){
                    $dialLogCallModel->importDialLogCall($data);
                }
            }
        }

        return $result;
    }

    /**
     * @param int|DialLogModel $dialLog
     * @param int|UserEntity $user
     * @return FunctionResult
     */
    public function stopDialByUser($dialLog, $user)
    {
        $result = new FunctionResult();

        $userModel = UserModel::getInstance();

        if (!$user instanceof UserEntity){
            $user = $userModel->getUserByUser($user);
        }

        if (!$user->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('user_not_found'));
            return $result;
        }

        $opts = $this->getCond();
        $opts->with($this->getCond(DialLogModel::WITH_DIAL_RULE)
            ->with(DialRuleModel::WITH_USER)
                ->where('user_id = ?', $user->getId()));

        $dialLog = $this->getDialLogByDialLog($dialLog, $opts);
        if (!$dialLog->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_log_not_found'));
            return $result;
        }

        $callPath = Zend_Registry::get('Asterisk')->outgoing . $user->getPhone();
        if (file_exists($callPath)){
            $fileData = file_get_contents($callPath);
            if (strpos($fileData, 'Account: ' . $dialLog->getHash() . '_') !== false){
                unlink ($callPath);
            }
        }

        return $result;
    }

    /**
     * @param int|DialLogModel $dialRule
     * @return FunctionResult
     */
    public function stopDial($dialLog)
    {
        $result = new FunctionResult();

        $opts = $this->getCond();
        $opts->with($this->getCond(DialLogModel::WITH_DIAL_RULE)
            ->with(DialRuleModel::WITH_USER_LIST));

        $dialLog = $this->getDialLogByDialLog($dialLog, $opts);
        if (!$dialLog->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_log_not_found'));
            return $result;
        }

        foreach ($dialLog->getDialRule()->getUserList() as $user){

            $callPath = Zend_Registry::get('Asterisk')->outgoing . $user->getPhone();

            if (file_exists($callPath)){
                $fileData = file_get_contents($callPath);
                if (strpos($fileData, 'Account: ' . $dialLog->getHash() . '_') !== false){
                    unlink ($callPath);
                }
            }
        }

        $data = array(
            'status' => 'canceled'
        );

        $this->updateDialLogByDialLog($dialLog, $data);
/*
        if (APPLICATION_ENV == "production"){
            $asteriskModel = AsteriskModel::getInstance();
            $asteriskModel->dialPlanReload();
        }
*/
        return $result;
    }
}