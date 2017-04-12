<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class CallFileEntity extends Model_Entity_Abstract
{
    /**
     * @param string $exten
     * @return float|int
     */
    public function getFileSize($exten = 'mb')
    {
        $filePath = Zend_Registry::get('dir')->files;

        $sizeByte = filesize($filePath . '/' . $this->getHash());

        switch ($exten){
            case 'mb':{
                return round($sizeByte / (1024 * 1024), 2);
            }
            default:{
                return $sizeByte;
            }
        }
    }

    public function getPath()
    {
        $filePath = Zend_Registry::get('dir')->files;

        return $filePath . $this->getHash();
    }

    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'dial_rule_id' => self::DATA_TYPE_INT,
            'admin_id' => self::DATA_TYPE_INT,
            'name' => self::DATA_TYPE_STRING,
            'hash' => self::DATA_TYPE_STRING,
            'create_date' => self::DATA_TYPE_STRING);
    }

    protected function _setupRelatedTypes()
    {
        $this->_relatedTypes = array('_dial_rule' => 'DialRuleEntity',
            '_admin' => 'AdminEntity');
    }

    /************************************************************************
     * Getters
     ************************************************************************/

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
     * Получить admin_id
     *
     * В mysql admin_id - ID администратора
     *
     * @return integer
     */
    public function getAdminId()
    {
        return $this->get('admin_id');
    }

    /**
     * Получить name
     *
     * В mysql name - Имя файла
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
     * В mysql name - Имя файла
     *
     * @return StringDecorator
     */
    public function getNameAsString()
    {
        return new StringDecorator($this->getName());
    }

    /**
     * Получить hash
     *
     * В mysql hash - Хеш
     *
     * @return string
     */
    public function getHash()
    {
        return $this->get('hash');
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

    /**
     * Получить связанную сущность admin
     *
     * @return AdminEntity
     */
    public function getAdmin()
    {
        return $this->getRelated('_admin');
    }
}
