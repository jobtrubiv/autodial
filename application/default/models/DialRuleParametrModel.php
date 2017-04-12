<?php
/**
 * Модель 'DialRuleParametr'
 */
class DialRuleParametrModel extends DialRuleParametrModelAbstract
{
    public $_actionArray = array(
        'play_file',
        'speech',
        'digit'
    );

    public $_digitActionArray = array(
        'hangup' => 'Отмена вызова',
        'queue' => 'Очередь'
    );

    /**
     * @param int|DialRuleEntity $dialRule
     * @param array $dialRuleParametrData
     * @return FunctionResult
     */
    public function add($dialRule, $dialRuleParametrData)
    {
        $result = new FunctionResult();

        if (!$dialRule instanceof DialRuleEntity){
            $dialRule = $this->getDialRuleByDialRule($dialRule);
        }

        if (!$dialRule->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'));
            return $result;
        }

        if (!in_array($dialRuleParametrData['action'], $this->_actionArray)){
            $result->add(TranslateModel::getTranslateMessageByCode('wrong_action'));
            return $result;
        }

        if (!isset($dialRuleParametrData['priority'])){
            $lastPriority = $this->getLastPriority($dialRule);

            $dialRuleParametrData['priority'] = ($lastPriority + 1);
        }

        if ($dialRuleParametrData['action'] == 'play_file'){
            $callFileModel = CallFileModel::getInstance();

            $callFile = $callFileModel->getCallFileByCallFile($dialRuleParametrData['action_data']);
            if (!$callFile->exists()){
                $result->add(TranslateModel::getTranslateMessageByCode('call_file_not_found'));
                return $result;
            }

            $dialRuleParametrData['action_data'] = $callFile->getHash();
        }

        $dialRuleParametrData['dial_rule_id'] = $dialRule->getId();

        $_result = $this->importDialRuleParametr($dialRuleParametrData);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('import_dial_rule_parametr_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|DialRuleParametrEntity $dialRule
     * @param array $dialRuleParametrData
     * @return FunctionResult
     */
    public function edit($dialRuleParametr, $dialRuleParametrData)
    {
        $result = new FunctionResult();

        if (!$dialRuleParametr instanceof DialRuleParametrEntity){
            $dialRuleParametr = $this->getDialRuleParametrByDialRuleParametr($dialRuleParametr);
        }

        if (!$dialRuleParametr->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_parametr_not_found'));
            return $result;
        }



        return $result;
    }

    /**
     * @param int|DialRuleParametrEntity $dialRule
     * @return FunctionResult
     */
    public function deleted($dialRuleParametr)
    {
        $result = new FunctionResult();

        if (!$dialRuleParametr instanceof DialRuleParametrEntity){
            $dialRuleParametr = $this->getDialRuleParametrByDialRuleParametr($dialRuleParametr);
        }

        if (!$dialRuleParametr->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_rule_parametr_not_found'));
            return $result;
        }

        $this->deleteDialRuleParametrByDialRuleParametr($dialRuleParametr);

        return $result;
    }

    /**
     * @param int|DialRuleEntity $dialRule
     * @return int
     */
    public function getLastPriority($dialRule)
    {
        $opts = $this->getCond();
        $opts->order('priority DESC');

        $dialRuleParametr = $this->getDialRuleParametrByDialRule($dialRule);

        return $dialRuleParametr->getPriority();
    }
}