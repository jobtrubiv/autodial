<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class DialLogCallEntity extends Model_Entity_Abstract
{
    public function getCallDigitData()
    {
        if (!$this->getCallDigit()){
            return $this->getCallDigit();
        }

        $dialLogCallModel = DialLogCallModel::getInstance();

        $opts = $dialLogCallModel->getCond();
        $opts->with(DialLogCallModel::WITH_DIAL_LOG);

        $dialLogCall = $dialLogCallModel->getDialLogCallByDialLogCall($this, $opts);

        $dialRuleParametrModel = DialRuleParametrModel::getInstance();

        $opts = $dialRuleParametrModel->getCond();
        $opts->where('action = ?', 'digit')
            ->where('action_data LIKE ?', $this->getCallDigit() . '_%');

        $dialRuleParametr = $dialRuleParametrModel->getDialRuleParametrByDialRule($dialLogCall->getDialLog()->getDialRuleId(), $opts);
        if ($dialRuleParametr->exists()){
            $actionDataArray = explode('_', $dialRuleParametr->getActionData());

            $numberAction = $actionDataArray[1];

            $nubmerActionModel = NumberActionModel::getInstance();
            $nubmerAction = $nubmerActionModel->getNumberActionByNumberAction($numberAction);
            if ($nubmerAction->exists()){
                return $this->getCallDigit() . ' (' . $nubmerAction->getName() . ')';
            }
        }
    }

    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'dial_log_id' => self::DATA_TYPE_INT,
            'user_id' => self::DATA_TYPE_INT,
            'phone' => self::DATA_TYPE_STRING,
            'duration' => self::DATA_TYPE_INT,
            'call_digit' => self::DATA_TYPE_INT,
            'status' => self::DATA_TYPE_STRING,
            'create_date' => self::DATA_TYPE_STRING);
    }

    protected function _setupRelatedTypes()
    {
        $this->_relatedTypes = array('_dial_log' => 'DialLogEntity',
            '_user' => 'UserEntity');
    }

    /************************************************************************
     * Getters
     ************************************************************************/

    /**
     * Получить dial_log_id
     *
     * В mysql dial_log_id
     *
     * @return integer
     */
    public function getDialLogId()
    {
        return $this->get('dial_log_id');
    }

    /**
     * Получить user_id
     *
     * В mysql user_id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->get('user_id');
    }

    /**
     * Получить phone
     *
     * В mysql phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->get('phone');
    }

    /**
     * Получить phone в декораторе String
     *
     * В mysql phone
     *
     * @return StringDecorator
     */
    public function getPhoneAsString()
    {
        return new StringDecorator($this->getPhone());
    }

    /**
     * Получить duration
     *
     * В mysql duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->get('duration');
    }

    /**
     * Получить call_digit
     *
     * В mysql call_digit
     *
     * @return integer
     */
    public function getCallDigit()
    {
        return $this->get('call_digit');
    }

    /**
     * Получить status
     *
     * В mysql status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->get('status');
    }

    /**
     * Проверить статус == 'busy'
     *
     * В mysql status
     *
     * @return boolean
     */
    public function isBusy()
    {
        return $this->getStatus() == 'busy';
    }

    /**
     * Проверить статус == 'answered'
     *
     * В mysql status
     *
     * @return boolean
     */
    public function isAnswered()
    {
        return $this->getStatus() == 'answered';
    }

    /**
     * Проверить статус == 'no_answered'
     *
     * В mysql status
     *
     * @return boolean
     */
    public function isNoAnswered()
    {
        return $this->getStatus() == 'no_answered';
    }

    /**
     * Проверить статус == 'failed'
     *
     * В mysql status
     *
     * @return boolean
     */
    public function isFailed()
    {
        return $this->getStatus() == 'failed';
    }

    /**
     * Получить create_date
     *
     * В mysql create_date
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
     * В mysql create_date
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
     * Получить связанную сущность dial_log
     *
     * @return DialLogEntity
     */
    public function getDialLog()
    {
        return $this->getRelated('_dial_log');
    }

    /**
     * Получить связанную сущность user
     *
     * @return UserEntity
     */
    public function getUser()
    {
        return $this->getRelated('_user');
    }

}
