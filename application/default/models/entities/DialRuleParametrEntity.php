<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class DialRuleParametrEntity extends Model_Entity_Abstract
{
    public function getFormatedActionData()
    {
        $callFileModel = CallFileModel::getInstance();

        if ($this->isActionPlayFile()){


            return $callFileModel->getCallFileByDialRuleAndHash($this->getDialRuleId(), $this->getActionData())->getName();
        }elseif ($this->isActionDigit()){
            $actionData = explode('_', $this->getActionData());

            $data = 'По цифре - ' . $actionData[0];

            $numberActionModel = NumberActionModel::getInstance();

            $numberAction = $numberActionModel->getNumberActionByNumberAction($actionData[1]);

            $data .= ' ' . $numberAction->getName();

            if (count($actionData) == 3 ){
                $data .= ' - ' . $actionData[2];
            }elseif (count($actionData) == 4){
                $callFile = $callFileModel->getCallFileByCallFile($actionData[3]);
                if ($callFile->exists()){
                    $data .= ' - ' . $callFile->getName();
                }
            }

            return $data;
        }else{
            return $this->getActionData();
        }
    }

    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'dial_rule_id' => self::DATA_TYPE_INT,
            'priority' => self::DATA_TYPE_INT,
            'action' => self::DATA_TYPE_STRING,
            'action_data' => self::DATA_TYPE_STRING,
            'create_date' => self::DATA_TYPE_STRING);
    }

    protected function _setupRelatedTypes()
    {
        $this->_relatedTypes = array('_dial_rule' => 'DialRuleEntity');
    }

    /************************************************************************
     * Getters
     ************************************************************************/

    /**
     * Получить dial_rule_id
     *
     * В mysql dial_rule_id - ID правила
     *
     * @return integer
     */
    public function getDialRuleId()
    {
        return $this->get('dial_rule_id');
    }

    /**
     * Получить priority
     *
     * В mysql priority - Приоритет
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->get('priority');
    }

    /**
     * Получить action
     *
     * В mysql action - Действие
     *
     * @return string
     */
    public function getAction()
    {
        return $this->get('action');
    }

    /**
     * Проверить 'action' == 'play_file'
     *
     * В mysql action - Действие
     *
     * @return boolean
     */
    public function isActionPlayFile()
    {
        return $this->getAction() == 'play_file';
    }

    /**
     * Проверить 'action' == 'speech'
     *
     * В mysql action - Действие
     *
     * @return boolean
     */
    public function isActionSpeech()
    {
        return $this->getAction() == 'speech';
    }

    /**
     * Проверить 'action' == 'digit'
     *
     * В mysql action - Действие
     *
     * @return boolean
     */
    public function isActionDigit()
    {
        return $this->getAction() == 'digit';
    }

    /**
     * Получить action_data
     *
     * В mysql action_data - Параметр
     *
     * @return string
     */
    public function getActionData()
    {
        return $this->get('action_data');
    }

    /**
     * Получить action_data в декораторе String
     *
     * В mysql action_data - Параметр
     *
     * @return StringDecorator
     */
    public function getActionDataAsString()
    {
        return new StringDecorator($this->getActionData());
    }

    /**
     * Получить create_date
     *
     * В mysql create_date - Дата добавления
     *
     * @return string
     */
    public function getCreateDate()
    {
        return $this->get('create_date');
    }

    /**
     * Получить create_date в декораторе Date
     *
     * В mysql create_date - Дата добавления
     *
     * @return DateDecorator
     */
    public function getCreateDateAsDate()
    {
        return new DateDecorator($this->getCreateDate());
    }

    /************************************************************************
     * Related
     ************************************************************************/

    /**
     * Получить связанную сущность dial_rule
     *
     * @return DialRuleEntity
     */
    public function getDialRule()
    {
        return $this->getRelated('_dial_rule');
    }


}
