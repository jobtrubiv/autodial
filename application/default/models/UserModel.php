<?php
/**
 * Модель 'User'
 */
class UserModel extends UserModelAbstract
{
    public static $_data = array(
        'surname' => '',
        'name' => '',
        'patronymic' => '',
        'email' => '',
        'phone' => '',
    );

    /**
     * @param array $userData
     * @return FunctionResult
     */
    public function add($dialRule, $userData)
    {
        $result = new FunctionResult();

        $dialRuleModel = DialRuleModel::getInstance();
        if (!$dialRule instanceof DialRuleEntity){
            $dialRule = $dialRuleModel->getDialRuleByDialRule($dialRule);
        }

        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        $validateResult = $this->validate($userData);
        if ($validateResult->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_validate_error'));
            $result->setResult($validateResult);
            return $result;
        }

        $userData['phone'] = str_replace('+', '', $userData['phone']);
        $userData['dial_rule_id'] = $dialRule->getId();

        $checkUser = $this->getUserByDialRuleAndPhone($dialRule, $userData['phone']);
        if ($checkUser->exists()){

            if ($checkUser->isDeleted()){
                $this->setStatusActive($checkUser);

                $result = $this->edit($checkUser, $userData);
                //return $result;
            }else{
                if (strtolower($userData['identificator_first']) != strtolower($checkUser->getIdentificatorFirst())){
                    $identificatorModel = IdentifictorModel::getInstance();

                    $opts = $identificatorModel->getCond();
                    $opts->where('identificator = ?', $userData['identificator_first']);

                    $identificator = $identificatorModel->getIdentifictorByUser($checkUser, $opts);
                    if (!$identificator->exists()){
                        $data = array(
                            'user_id' => $checkUser->getId(),
                            'identificator' => $userData['identificator_first']
                        );

                        $identificatorModel->importIdentifictor($data);
                    }
                }

            }

            return $result;
        }

        $_result = $this->importUser($userData);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('import_user_error'));
            return $result;
        }

        return $result;
    }

    public function importFromFile($dialRule, $filePath, $fileName)
    {
        $result = new FunctionResult();

        $dialRuleModel = DialRuleModel::getInstance();
        if (!$dialRule instanceof DialRuleEntity){
            $dialRule = $dialRuleModel->getDialRuleByDialRule($dialRule);
        }

        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        if (!file_exists($filePath)){
            $result->add(TranslateModel::getTranslateMessageByCode('file_not_found'));
            return $result;
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (strtolower($extension) != 'csv'){
            $result->add(TranslateModel::getTranslateMessageByCode('file_wrong_extension'));
            return $result;
        }

        $fileArray = file($filePath);
        unset($fileArray[0]);

        foreach ($fileArray as $userLine) {
            $userLine = iconv( "Windows-1251", "UTF-8", $userLine );

            $userArray = explode(';', trim($userLine));

            $identificatorFirst = trim($userArray[1]);
            $district = trim($userArray[2]);
            $region = trim($userArray[3]);
            $fio = trim($userArray[4]);
            $fullAddress = trim($userArray[5]);
            $phone = trim($userArray[6]);
            $identificatorSecond = trim($userArray[7]);
            $email = trim($userArray[8]);

            $fioArray = explode(' ', $fio);

            $surname = trim($fioArray[0]);
            $name = trim($fioArray[1]);
            $patronymic = trim($fioArray[2]);

            $phone = str_replace('+', '', $phone);

            $data = array(
                'surname' => $surname,
                'name' => $name,
                'patronymic' => $patronymic,
                'email' => $email,
                'phone' => $phone,
                'full_address' => $fullAddress,
                'district' => $district,
                'region' => $region,
                'identificator_first' => $identificatorFirst,
                'identificator_second' => $identificatorSecond
            );

            $this->add($dialRule, $data);
        }

        return $result;
    }

    /**
     * @param int|UserEntity $user
     * @param array $userData
     * @return FunctionResult
     */
    public function edit($user, $userData)
    {
        $result = new FunctionResult();

        if (!$user instanceof UserEntity){
            $user = $this->getUserByUser($user);
        }

        if (!$user->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('user_not_found'));
            return $result;
        }

        $validateResult = $this->validate($userData);
        if ($validateResult->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('user_validate_error'));
            $result->setResult($validateResult);
            return $result;
        }

        $userData['phone'] = str_replace('+', '', $userData['phone']);

        $_result = $this->updateUserByUser($user, $userData);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('update_user_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|UserEntity $user
     * @return FunctionResult
     */
    public function setStatusActive($user)
    {
        $result = new FunctionResult();

        if (!$user instanceof UserEntity){
            $user = $this->getUserByUser($user);
        }

        if (!$user->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('user_not_found'));
            return $result;
        }

        if ($user->isActive()){
            return $result;
        }

        $data = array(
            'status' => 'active'
        );

        $_result = $this->updateUserByUser($user, $data);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('set_active_user_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|UserEntity $user
     * @return FunctionResult
     */
    public function setStatusDeleted($user)
    {
        $result = new FunctionResult();

        if (!$user instanceof UserEntity){
            $user = $this->getUserByUser($user);
        }

        if (!$user->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('user_not_found'));
            return $result;
        }

        if ($user->isDeleted()){
            return $result;
        }

        $data = array(
            'status' => 'deleted'
        );

        $_result = $this->updateUserByUser($user, $data);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('deleted_user_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param array $userData
     * @return ValidateResult
     */
    public function validate($userData)
    {
        $validateResult = new ValidateResult($userData);

        if (empty($userData['surname'])){
            $validateResult->addError('surname', TranslateModel::getTranslateMessageByCode('surname_is_not_correct'));
        }else{
            if (preg_match( '/^[а-яА-ЯёЁa-zA-Z ]/u', $userData['surname']) == 0){
                $validateResult->addError('surname', TranslateModel::getTranslateMessageByCode('surname_is_not_correct'));
            }
        }

        if (empty($userData['name'])){
            $validateResult->addError('name', TranslateModel::getTranslateMessageByCode('name_is_not_correct'));
        }else{
            if (preg_match( '/^[а-яА-ЯёЁa-zA-Z ]/u', $userData['name']) == 0){
                $validateResult->addError('name', TranslateModel::getTranslateMessageByCode('name_is_not_correct'));
            }
        }

        if (!empty($adminData['email'])){
            if (preg_match( '/[-\w.]+@([A-z0-9][-A-z0-9]+\.)+[A-z]{2,4}$/', $userData['email']) == 0){
                $validateResult->addError('email', TranslateModel::getTranslateMessageByCode('email_is_not_correct'));
            }
        }

        if (empty($userData['phone'])){
            $validateResult->addError('phone', TranslateModel::getTranslateMessageByCode('phone_is_not_correct'));
        }else{
            if (preg_match( '/^[0-9-+ ]{6,14}/u', $userData['phone']) == 0){
                $validateResult->addError('phone', TranslateModel::getTranslateMessageByCode('phone_is_not_correct'));
            }
        }

        return $validateResult;
    }
}