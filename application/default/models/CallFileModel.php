<?php
/**
 * Модель 'CallFile'
 */
class CallFileModel extends CallFileModelAbstract
{
    /**
     * @param int|AdminEntity $admin
     * @param string $filePath
     * @return FunctionResult
     */
    public function add($dialRule, $admin, $filePath, $fileName)
    {
        $result = new FunctionResult();

        $adminModel = AdminModel::getInstance();

        if (!$admin instanceof AdminEntity){
            $admin = $adminModel->getAdminByAdmin($admin);
        }

        if (!$admin->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_not_found'));
            return $result;
        }

        if ($admin->isTypeOperator()){
            $result->add(TranslateModel::getTranslateMessageByCode('not_allow'));
            return $result;
        }

        $dialRuleModel = DialRuleModel::getInstance();
        if (!$dialRule instanceof DialRuleEntity){
            $dialRule = $dialRuleModel->getDialRuleByDialRule($dialRule);
        }

        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        $extension = substr($fileName, strrpos($fileName, '.') + 1);
        if (!in_array($extension, array('wav'))){
            $result->add(TranslateModel::getTranslateMessageByCode('wrong_extension'));
            return $result;
        }

        $fileSavePath = Zend_Registry::get('dir')->files;
        mkdir($filePath, 0777, true);

        $fileNameHash = EncryptionModel::getInstance()->encrypt($admin->getId(). ':' . $fileName);

        if (copy($filePath, $fileSavePath . '/' .$fileNameHash )){
            $data = array(
                'dial_rule_id' => $dialRule->getId(),
                'admin_id' => $admin->getId(),
                'name' => $fileName,
                'hash' => $fileNameHash
            );

            $_result = $this->importCallFile($data);
            if ($_result->isError()){
                $result->add(TranslateModel::getTranslateMessageByCode('import_call_file_error'));
                return $result;
            }
        }

        return $result;
    }

    /**
     * @param int|CallFileEntity $callFile
     * @return FunctionResult
     */
    public function remove($callFile)
    {
        $result = new FunctionResult();

        $dialRuleParameterModel = DialRuleParametrModel::getInstance();

        if (!$callFile instanceof CallFileEntity){
            $callFile = $this->getCallFileByCallFile($callFile);
        }

        if (!$callFile->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('call_file_not_found'));
            return $result;
        }

        $fileSavePath = Zend_Registry::get('dir')->files;

        if (unlink($fileSavePath . '/' . $callFile->getHash())){
            $this->deleteCallFileByCallFile($callFile);
        }else{
            $result->add(TranslateModel::getTranslateMessageByCode('call_file_delete_error'));
            return $result;
        }

        $opts = $dialRuleParameterModel->getCond();
        $opts->where('action_data = ?', $callFile->getHash());

        $dialRuleParameterModel->deleteDialRuleParametr($opts);

        return $result;
    }
}