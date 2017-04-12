<?php
/**
 * Модель 'DialRule'
 */
class DialRuleModel extends DialRuleModelAbstract
{
    public static $_data = array(
        'name' => '',
        'max_retries' => '',
        'timeout' => '',
    );

    public static $_socrArray = array(
        'ул' => 'улица',
        'пер' => 'переулок',
        'пр' => 'проспект',
        'бул' => 'бульвар',
        'д' => 'дом',
        'ш' => 'шоссе',
        'кв' => 'квартира',
        'наб' => 'набережная',
        'оф' => 'офис',
        'пл-дь' => 'площадь',
    );

    /**
     * @param array $dialRuleData
     * @return FunctionResult
     */
    public function add($dialRuleData)
    {
        $result = new FunctionResult();

        $validateResult = $this->validate($dialRuleData);
        if ($validateResult->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_validate_error'));
            $result->setResult($validateResult);
            return $result;
        }

        $checkDialRule = $this->getDialRuleByName($dialRuleData['name']);
        if ($checkDialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_exists'));
            $result->setResult($checkDialRule);
            return $result;
        }

        $_result = $this->importDialRule($dialRuleData);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('import_dial_rule_error'));
            return $result;
        }

        $result->setResult($_result->getResult());

        return $result;
    }

    /**
     * @param int|DialRuleEntity $dialRule
     * @param array $dialRuleData
     * @return FunctionResult
     */
    public function edit($dialRule, $dialRuleData)
    {
        $result = new FunctionResult();

        if (!$dialRule instanceof DialRuleEntity){
            $dialRule = $this->getDialRuleByDialRule($dialRule);
        }

        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        $validateResult = $this->validate($dialRuleData);
        if ($validateResult->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_validate_error'));
            $result->setResult($validateResult);
            return $result;
        }

        $checkDialRule = $this->getDialRuleByName($dialRuleData['name']);
        if ($checkDialRule->exists() && $checkDialRule->getId() != $dialRule->getId()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_exists'));
            $result->setResult($checkDialRule);
            return $result;
        }

        $_result = $this->updateDialRuleByDialRule($dialRule, $dialRuleData);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('update_dial_rule_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|DialRuleEntity $user
     * @return FunctionResult
     */
    public function setStatusActive($dialRule)
    {
        $result = new FunctionResult();

        if (!$dialRule instanceof DialRuleEntity){
            $dialRule = $this->getDialRuleByDialRule($dialRule);
        }

        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        if ($dialRule->isActive()){
            return $result;
        }

        $data = array(
            'status' => 'active'
        );

        $_result = $this->updateDialRuleByDialRule($dialRule, $data);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('set_dial_rule_user_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|DialRuleEntity $user
     * @return FunctionResult
     */
    public function setStatusDeleted($dialRule)
    {
        $result = new FunctionResult();

        if (!$dialRule instanceof DialRuleEntity){
            $dialRule = $this->getDialRuleByDialRule($dialRule);
        }

        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        if ($dialRule->isDeleted()){
            return $result;
        }

        $data = array(
            'status' => 'deleted'
        );

        $_result = $this->updateDialRuleByDialRule($dialRule, $data);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('set_dial_rule_user_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|DialRuleEntity $dialRule
     * @param AdminEntity $admin
     * @return FunctionResult
     */
    public function startDial($dialRule, $admin, $userIdArray = null)
    {
        $result = new FunctionResult();

        $userModel = UserModel::getInstance();
        $dialLogModel = DialLogModel::getInstance();
        $dialSettingsModel = DialSettingsModel::getInstance();

        $dialSettings = $dialSettingsModel->getActiveSettings();
        if (!$dialSettings->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_settings_not_found'));
            return $result;
        }

        $opts = $this->getCond();
        $opts->with(DialRuleModel::WITH_DIAL_RULE_PARAMETR_LIST);

        $dialRule = $this->getDialRuleByDialRule($dialRule, $opts);
        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        $opts = $userModel->getCond();
        $opts->with(UserModel::WITH_IDENTIFICTOR_LIST)
            ->where('status = ?', 'active')
            ->order('id ASC');

        if (count($userIdArray) != 0){
            $opts->where('id IN (?)', $userIdArray);
        }

        $userList = $userModel->getUserListByDialRule($dialRule, $opts);
        if (!$userList->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('user_list_is_empty'));
            return $result;
        }

        $hash = $dialLogModel->getNextHash();

        $extensionDataArray = array();
        $extensionArray = array();
        foreach ($userList as $user){

            $extension = $dialSettings->getContext() . '_' . $user->getId();

            $_result = $this->createExtensionFileByUser($dialRule, $extension, $user);
            if ($_result->isError()) {
                $result->add($_result);
                $dialLogModel->add($admin, $dialRule, $_result, $hash);
                return $result;
            }

            $extensionArray[$extension] = $user;
            $extensionDataArray[$user->getId()] = $_result->getResult();
        }


        //Формируем enxtensionConf
        $_result = $this->renewExtensionConf($extensionDataArray);
        if ($_result->isError()){
            $result->add($_result);
            $dialLogModel->add($admin, $dialRule, $_result, $hash);
            return $result;
        }

        $asterisk = AsteriskModel::getInstance();

        foreach ($extensionArray as $extension => $user){
            $_result = $asterisk->originateCall($user->getPhone(), $dialSettings, $hash, $extension, $dialRule->getMaxRetries());
            if ($_result->isError()){
                $result->add($_result);
            }
        }

        $dialLogResult = $dialLogModel->add($admin, $dialRule, $_result, $hash);

        $result->setResult($dialLogResult->getResult());

        return $result;
    }

    /**
     * @param DialRuleEntity $dialRule
     * @param string $extension
     * @param UserEntity $user
     * @return FunctionResult
     */
    public function createExtensionFileByUser($dialRule, $extension, $user)
    {
        $result = new FunctionResult();

        $callFileModel = CallFileModel::getInstance();

        $extensionArray = array(
            '[' . $extension . ']',
            'exten => s,1,Answer',
            //'exten => s,n,Set(finaldst=${EXTEN})',
            //'exten => s,n,Set(CDR(dst)=${finaldst})',
            'exten => s,n,Wait(1)',
        );

        foreach ($dialRule->getDialRuleParametrList() as $dialRuleParametr){
            if ($dialRuleParametr->isActionPlayFile()){

                $callfile = $callFileModel->getCallFileByDialRuleAndHash($dialRule, $dialRuleParametr->getActionData());
                if (!$callfile->exists()){
                    $result->add(TranslateModel::getTranslateMessageByCode('call_file_not_found'));
                    break;
                }

                $filePath = $fileSavePath = Zend_Registry::get('dir')->files . $callfile->getHash();
                $movefilePath = Zend_Registry::get('Asterisk')->monitoring . $callfile->getHash() . '_conv.wav';

                if (!copy($filePath, $movefilePath)) {
                    $result->add(TranslateModel::getTranslateMessageByCode('call_file_not_found'));
                    break;
                }

                $convertFilePath = Zend_Registry::get('Asterisk')->monitoring . $callfile->getHash() . '.wav';

                $this->convertoToAsteriskFormat($movefilePath, $convertFilePath);

                $extensionArray[] = 'exten => s,n,Background(' . Zend_Registry::get('Asterisk')->monitoring . $callfile->getHash() . ')';

            }elseif ($dialRuleParametr->isActionSpeech()){

                $fileName = md5('speech' . $user->getId() . $dialRuleParametr->getId());

                $filePath = $fileSavePath = Zend_Registry::get('dir')->files . $fileName . '.wav';

                $textToSpeech = $dialRuleParametr->getActionData();

                $textToSpeech = str_replace('{fio}', $user->getFullName(), $textToSpeech);
                $textToSpeech = str_replace('{phone}', $user->getPhone(), $textToSpeech);
                $textToSpeech = str_replace('{address}', $user->getFullAddress(), $textToSpeech);

                $identString = $user->getIdentificatorFirst();
                if ($user->getIdentifictorList()->exists()){
                    foreach ($user->getIdentifictorList() as $identifictor){
                        $identString .= ' ' . $identifictor->getIdentificator();
                    }
                }

                $textToSpeech = str_replace('{identificator_1}', $identString, $textToSpeech);
                $textToSpeech = str_replace('{identificator_2}', $user->getIdentificatorSecond(), $textToSpeech);

                $textToSpeech = $this->replaceSocr($textToSpeech);

                $_result = $this->generateSpeechFile($textToSpeech, $filePath);
                if ($_result->isError()){
                    $result->add(TranslateModel::getTranslateMessageByCode('generate_speech_error'));
                    break;
                }

                $movefilePath = Zend_Registry::get('Asterisk')->monitoring . $fileName . '_conv.wav';
                if (!copy($filePath, $movefilePath)) {
                    $result->add(TranslateModel::getTranslateMessageByCode('call_file_not_found'));
                    break;
                }

                $convertFilePath = Zend_Registry::get('Asterisk')->monitoring . $fileName . '.wav';

                $this->convertoToAsteriskFormat($movefilePath, $convertFilePath);

                $extensionArray[] = 'exten => s,n,Background(' . Zend_Registry::get('Asterisk')->monitoring . $fileName . ')';
                unlink($filePath);
            }elseif ($dialRuleParametr->isActionDigit()){

                $numberActionModel = NumberActionModel::getInstance();

                if (strpos($dialRuleParametr->getActionData(), '_') === false){
                    $result->add(TranslateModel::getTranslateMessageByCode('call_file_not_found'));
                    break;
                }

                $actionDataArray = explode('_', $dialRuleParametr->getActionData());

                $digit = intval($actionDataArray[0]);
                $action = $actionDataArray[1];
                $queue = $actionDataArray[2];
                $file = $actionDataArray[3];

                $numberAction = $numberActionModel->getNumberActionByNumberAction($action);
                if ($numberAction->exists()){
                    $extensionArray[] = 'exten => s,n,WaitExten(10)';

                    if ($numberAction->getCode() == 'queue'){
                        $extensionArray[] = 'exten => ' . $digit . ',1,Set(CDR(userfield)='.$digit.')';
                        $extensionArray[] = 'exten => ' . $digit . ',2,' . 'Queue('.$queue.')';
                    }elseif ($numberAction->getCode() == 'hangup'){
                        $extensionArray[] = 'exten => ' . $digit . ',1,Set(CDR(userfield)='.$digit.')';
                        $extensionArray[] = 'exten => ' . $digit . ',2,hangup';
                    }elseif($numberAction->getCode() == 'play'){
                        $callfile = $callFileModel->getCallFileByCallFile($file);
                        if (!$callfile->exists()){
                            $result->add(TranslateModel::getTranslateMessageByCode('call_file_not_found'));
                            break;
                        }

                        $filePath = $fileSavePath = Zend_Registry::get('dir')->files . $callfile->getHash();
                        $movefilePath = Zend_Registry::get('Asterisk')->monitoring . $callfile->getHash() . '_conv.wav';

                        if (!copy($filePath, $movefilePath)) {
                            $result->add(TranslateModel::getTranslateMessageByCode('call_file_not_found'));
                            break;
                        }

                        $convertFilePath = Zend_Registry::get('Asterisk')->monitoring . $callfile->getHash() . '.wav';

                        $this->convertoToAsteriskFormat($movefilePath, $convertFilePath);

                        $extensionArray[] = 'exten => ' . $digit . ',1,Set(CDR(userfield)='.$digit.')';
                        $extensionArray[] = 'exten => ' . $digit . ',2,Background(' . Zend_Registry::get('Asterisk')->monitoring . $callfile->getHash() . ')';
                    }elseif($numberAction->getCode() == 'repeate'){
                        $extensionArray[] = 'exten => ' . $digit . ',1,Set(CDR(userfield)='.$digit.')';
                        $extensionArray[] = 'exten => ' . $digit . ',2,Wait(2)';
                        $extensionArray[] = 'exten => ' . $digit . ',3,GoTo(s,1)';
                    }else{
                        $extensionArray[] = 'exten => ' . $digit . ',1,Set(CDR(userfield)='.$digit.')';
                        $extensionArray[] = 'exten => ' . $digit . ',2,Wait(2)';
                    }
                }
            }
        }

        $extensionArray[] = 'exten => s,n,Wait(15)';
        $extensionArray[] = 'exten => s,n,hangup';

        $result->setResult($extensionArray);

        return $result;
    }

    private function replaceSocr($text)
    {
        $resultText = strtolower($text);

        $textArray = explode(' ', $resultText);

        $resultTextArray = array();
        foreach ($textArray as $key =>  $textData){
            foreach (self::$_socrArray as $socr => $full){

                $textDataTest = str_replace('.', '', $textData);
                if ($textDataTest == $socr){
                    $textArray[$key] = $full;
                }
            }
        }

        return implode($textArray, ' ');
    }

    /**
     * @param array $extensionArray
     * @param DialRuleEntity $dialRule
     * @return FunctionResult
     */
    private function renewExtensionConf($extensionArray)
    {
        $result = new FunctionResult();

        $startMark = ';AutoDialStartExntension';
        $endMark = ';AutoDialEndExntension';

        $extensionConfPath = Zend_Registry::get('Asterisk')->extenconf;
        if (!file_exists($extensionConfPath)){
            $result->add(TranslateModel::getTranslateMessageByCode('extension_conf_file_not_found'));
            return $result;
        }

        $extensionConfData = trim(file_get_contents($extensionConfPath));

        $startPosition = strpos($extensionConfData, $startMark);
        $endPosition = strpos($extensionConfData, $endMark);

        //Удаляем старые настройки
        if ($startPosition !== false && $endPosition !== false ){
            $oldExtension = substr($extensionConfData, $startPosition, $endPosition);

            $extensionConfData = trim(str_replace($oldExtension, '', $extensionConfData));
            $extensionConfData = trim(str_replace($endPosition, '', $extensionConfData));
        }

        $extensionConfData .= PHP_EOL . PHP_EOL .  $startMark;
        foreach ($extensionArray as $extension){
            $extensionConfData .= PHP_EOL . implode(PHP_EOL, $extension) . PHP_EOL;
        }
        $extensionConfData .= PHP_EOL . $endMark;

        file_put_contents($extensionConfPath, $extensionConfData);

        if (APPLICATION_ENV == "production"){
            $asteriskModel = AsteriskModel::getInstance();
            $asteriskModel->dialPlanReload();
        }

        return $result;
    }

    /**
     * Генерация речи по тексту
     *
     * @param string $message
     * @param string $fileSaveName
     * @return FunctionResult
     */
    public function generateSpeechFile($message, $fileSaveName)
    {
        $result = new FunctionResult();

        $apiKey = Zend_Registry::get('Yandex')->key;

        $wget = 'wget -U "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5" ';
        $wget .= '"https://tts.voicetech.yandex.net/generate?text=' . $message . '&format=wav&lang=ru-RU&speaker=jane&emotion=good&key=' . $apiKey . '" -O ' . $fileSaveName;

        exec($wget);

        if (!file_exists($fileSaveName)){
            $result->add(TranslateModel::getTranslateMessageByCode('generate_speech_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param string $filePath
     * @param string $convetrFilePath
     */
    private function convertoToAsteriskFormat($filePath, $convetrFilePath)
    {
        if (file_exists($convetrFilePath)){
            unlink($convetrFilePath);
        }

        //exec('sox -v 0.5 ' . $filePath . ' -t wav -b 16 -r 8000 -c 1 ' . $convetrFilePath);
        exec('sox -v 0.5 ' . $filePath . ' -t wav -2 -r 8000 -c 1 ' . $convetrFilePath);
        unlink($filePath);
    }

    /**
     * @param array $dialRuleData
     * @return ValidateResult
     */
    public function validate($dialRuleData)
    {
        $validateResult = new ValidateResult($dialRuleData);

        if (empty($dialRuleData['name'])){
            $validateResult->addError('name', TranslateModel::getTranslateMessageByCode('name_is_not_correct'));
        }else{
            if (preg_match( '/^[а-яА-ЯёЁa-zA-Z]/u', $dialRuleData['name']) == 0){
                $validateResult->addError('name', TranslateModel::getTranslateMessageByCode('name_is_not_correct'));
            }
        }

        return $validateResult;
    }
}