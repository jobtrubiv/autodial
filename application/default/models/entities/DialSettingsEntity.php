<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class DialSettingsEntity extends Model_Entity_Abstract
{
    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'trunk' => self::DATA_TYPE_STRING,
            'caller_id' => self::DATA_TYPE_STRING,
            'context' => self::DATA_TYPE_STRING,
            'retry_time' => self::DATA_TYPE_INT,
            'wait_time' => self::DATA_TYPE_INT,
            'active' => self::DATA_TYPE_STRING);
    }


    /************************************************************************
     * Getters
     ************************************************************************/

    /**
     * Получить trunk
     *
     * В mysql trunk
     *
     * @return string
     */
    public function getTrunk()
    {
        return $this->get('trunk');
    }

    /**
     * Получить trunk в декораторе String
     *
     * В mysql trunk
     *
     * @return StringDecorator
     */
    public function getTrunkAsString()
    {
        return new StringDecorator($this->getTrunk());
    }

    /**
     * Получить caller_id
     *
     * В mysql caller_id
     *
     * @return string
     */
    public function getCallerId()
    {
        return $this->get('caller_id');
    }

    /**
     * Получить caller_id в декораторе String
     *
     * В mysql caller_id
     *
     * @return StringDecorator
     */
    public function getCallerIdAsString()
    {
        return new StringDecorator($this->getCallerId());
    }

    /**
     * Получить context
     *
     * В mysql context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->get('context');
    }

    /**
     * Получить context в декораторе String
     *
     * В mysql context
     *
     * @return StringDecorator
     */
    public function getContextAsString()
    {
        return new StringDecorator($this->getContext());
    }

    /**
     * Получить retry_time
     *
     * В mysql retry_time
     *
     * @return integer
     */
    public function getRetryTime()
    {
        return $this->get('retry_time');
    }

    /**
     * Получить wait_time
     *
     * В mysql wait_time
     *
     * @return integer
     */
    public function getWaitTime()
    {
        return $this->get('wait_time');
    }

    /**
     * Получить active
     *
     * В mysql active
     *
     * @return string
     */
    public function getActive()
    {
        return $this->get('active');
    }

    /**
     * Проверить 'active' == y
     *
     * В mysql active
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->getActive() == 'y';
    }
}
