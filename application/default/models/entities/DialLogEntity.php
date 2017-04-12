<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class DialLogEntity extends Model_Entity_Abstract
{
    /**
     * @return bool
     */
    public function isRun()
    {
        return $this->getActiveCall() > 0;
    }

    /**
     * @return bool
     * @throws Zend_Exception
     */
    public function isEnd()
    {
        return $this->getActiveCall() == 0;
    }

    public function getActiveCall()
    {
        $newfile = Zend_Registry::get('Asterisk')->outgoing;
        $dir = opendir($newfile);
        $count = 0;
        while($file = readdir($dir)){
            if($file == '.' || $file == '..' || is_dir($dir . $file)){
                continue;
            }

            $fileData = file_get_contents($newfile . $file);

            if (strpos($fileData, 'Account: ' . $this->getHash() . '_') !== false){
                $count++;
            }
        }

        return $count;
    }

    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'admin_id' => self::DATA_TYPE_INT,
            'dial_rule_id' => self::DATA_TYPE_INT,
            'status' => self::DATA_TYPE_STRING,
            'error' => self::DATA_TYPE_STRING,
            'hash' => self::DATA_TYPE_INT,
            'create_date' => self::DATA_TYPE_STRING);
    }

    protected function _setupRelatedTypes()
    {
        $this->_relatedTypes = array('_admin' => 'AdminEntity',
            '_dial_rule' => 'DialRuleEntity',
            '_dial_log_call' => 'DialLogCallEntity',
            '_dial_log_call_list' => 'DialLogCallCollection');
    }

    /************************************************************************
     * Getters
     ************************************************************************/

    /**
     * Получить admin_id
     *
     * В mysql admin_id
     *
     * @return integer
     */
    public function getAdminId()
    {
        return $this->get('admin_id');
    }

    /**
     * Получить dial_rule_id
     *
     * В mysql dial_rule_id
     *
     * @return integer
     */
    public function getDialRuleId()
    {
        return $this->get('dial_rule_id');
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
     * Проверить статус == 'success'
     *
     * В mysql status
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->getStatus() == 'success';
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
     * Проверить статус == 'canceled'
     *
     * В mysql status
     *
     * @return boolean
     */
    public function isCanceled()
    {
        return $this->getStatus() == 'canceled';
    }

    /**
     * Получить error
     *
     * В mysql error
     *
     * @return string
     */
    public function getError()
    {
        return $this->get('error');
    }

    /**
     * Получить error в декораторе String
     *
     * В mysql error
     *
     * @return StringDecorator
     */
    public function getErrorAsString()
    {
        return new StringDecorator($this->getError());
    }

    /**
     * Получить hash
     *
     * В mysql hash
     *
     * @return integer
     */
    public function getHash()
    {
        return $this->get('hash');
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
     * Получить связанную сущность admin
     *
     * @return AdminEntity
     */
    public function getAdmin()
    {
        return $this->getRelated('_admin');
    }

    /**
     * Получить связанную сущность dial_rule
     *
     * @return DialRuleEntity
     */
    public function getDialRule()
    {
        return $this->getRelated('_dial_rule');
    }

    /**
     * Получить связанную сущность dial_log_call
     *
     * @return DialLogCallEntity
     */
    public function getDialLogCall()
    {
        return $this->getRelated('_dial_log_call');
    }

    /**
     * Получить набор связанных сущностей dial_log_call
     *
     * @return DialLogCallCollection|DialLogCallEntity[]
     */
    public function getDialLogCallList()
    {
        return $this->getRelated('_dial_log_call_list');
    }

}
