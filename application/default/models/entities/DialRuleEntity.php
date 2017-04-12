<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class DialRuleEntity extends Model_Entity_Abstract
{
    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'name' => self::DATA_TYPE_STRING,
            'max_retries' => self::DATA_TYPE_INT,
            'timeout' => self::DATA_TYPE_INT,
            'status' => self::DATA_TYPE_STRING,
            'create_date' => self::DATA_TYPE_STRING);
    }

    protected function _setupRelatedTypes()
    {
        $this->_relatedTypes = array('_call_file' => 'CallFileEntity',
            '_call_file_list' => 'CallFileCollection',
            '_dial_log' => 'DialLogEntity',
            '_dial_log_list' => 'DialLogCollection',
            '_dial_rule_parametr' => 'DialRuleParametrEntity',
            '_dial_rule_parametr_list' => 'DialRuleParametrCollection',
            '_user' => 'UserEntity',
            '_user_list' => 'UserCollection');
    }

    /************************************************************************
     * Getters
     ************************************************************************/

    /**
     * Получить name
     *
     * В mysql name - Название
     *
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * Получить name в декораторе String
     *
     * В mysql name - Название
     *
     * @return StringDecorator
     */
    public function getNameAsString()
    {
        return new StringDecorator($this->getName());
    }

    /**
     * Получить max_retries
     *
     * В mysql max_retries
     *
     * @return integer
     */
    public function getMaxRetries()
    {
        return $this->get('max_retries');
    }

    /**
     * Получить timeout
     *
     * В mysql timeout
     *
     * @return integer
     */
    public function getTimeout()
    {
        return $this->get('timeout');
    }

    /**
     * Получить status
     *
     * В mysql status - Статус
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->get('status');
    }

    /**
     * Проверить статус == 'active'
     *
     * В mysql status - Статус
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->getStatus() == 'active';
    }

    /**
     * Проверить статус == 'deleted'
     *
     * В mysql status - Статус
     *
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->getStatus() == 'deleted';
    }

    /**
     * Получить create_date
     *
     * В mysql create_date - Дата создания
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
     * В mysql create_date - Дата создания
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
     * Получить связанную сущность call_file
     *
     * @return CallFileEntity
     */
    public function getCallFile()
    {
        return $this->getRelated('_call_file');
    }

    /**
     * Получить набор связанных сущностей call_file
     *
     * @return CallFileCollection|CallFileEntity[]
     */
    public function getCallFileList()
    {
        return $this->getRelated('_call_file_list');
    }

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
     * Получить набор связанных сущностей dial_log
     *
     * @return DialLogCollection|DialLogEntity[]
     */
    public function getDialLogList()
    {
        return $this->getRelated('_dial_log_list');
    }

    /**
     * Получить связанную сущность dial_rule_parametr
     *
     * @return DialRuleParametrEntity
     */
    public function getDialRuleParametr()
    {
        return $this->getRelated('_dial_rule_parametr');
    }

    /**
     * Получить набор связанных сущностей dial_rule_parametr
     *
     * @return DialRuleParametrCollection|DialRuleParametrEntity[]
     */
    public function getDialRuleParametrList()
    {
        return $this->getRelated('_dial_rule_parametr_list');
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

    /**
     * Получить набор связанных сущностей user
     *
     * @return UserCollection|UserEntity[]
     */
    public function getUserList()
    {
        return $this->getRelated('_user_list');
    }

}
