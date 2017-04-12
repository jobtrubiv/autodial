<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class UserEntity extends Model_Entity_Abstract
{
    public function getFullName()
    {
        return $this->getSurname() . ' ' . $this->getName() . ' ' . $this->getPatronymic();
    }

    /**
     * @param DialLogEntity $dialLog
     * @return bool
     */
    public function isEndCall($dialLog)
    {
        $callPath = Zend_Registry::get('Asterisk')->outgoing . $this->getPhone();

        if (file_exists($callPath)){
            $fileData = file_get_contents($callPath);
            if (strpos($fileData, 'Account: ' . $dialLog->getHash() . '_') !== false){
                return false;
            }
        }

        return true;
    }

    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'dial_rule_id' => self::DATA_TYPE_INT,
            'surname' => self::DATA_TYPE_STRING,
            'name' => self::DATA_TYPE_STRING,
            'patronymic' => self::DATA_TYPE_STRING,
            'email' => self::DATA_TYPE_STRING,
            'phone' => self::DATA_TYPE_STRING,
            'full_address' => self::DATA_TYPE_STRING,
            'district' => self::DATA_TYPE_STRING,
            'region' => self::DATA_TYPE_STRING,
            'identificator_first' => self::DATA_TYPE_STRING,
            'identificator_second' => self::DATA_TYPE_STRING,
            'status' => self::DATA_TYPE_STRING,
            'create_date' => self::DATA_TYPE_STRING);
    }

    protected function _setupRelatedTypes()
    {
        $this->_relatedTypes = array('_dial_rule' => 'DialRuleEntity',
            '_dial_log_call' => 'DialLogCallEntity',
            '_dial_log_call_list' => 'DialLogCallCollection',
            '_identifictor' => 'IdentifictorEntity',
            '_identifictor_list' => 'IdentifictorCollection');
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
     * Получить surname
     *
     * В mysql surname - Фамилия
     *
     * @return string
     */
    public function getSurname()
    {
        return $this->get('surname');
    }

    /**
     * Получить surname в декораторе String
     *
     * В mysql surname - Фамилия
     *
     * @return StringDecorator
     */
    public function getSurnameAsString()
    {
        return new StringDecorator($this->getSurname());
    }

    /**
     * Получить name
     *
     * В mysql name - Имя
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
     * В mysql name - Имя
     *
     * @return StringDecorator
     */
    public function getNameAsString()
    {
        return new StringDecorator($this->getName());
    }

    /**
     * Получить patronymic
     *
     * В mysql patronymic - Отчетсво
     *
     * @return string
     */
    public function getPatronymic()
    {
        return $this->get('patronymic');
    }

    /**
     * Получить patronymic в декораторе String
     *
     * В mysql patronymic - Отчетсво
     *
     * @return StringDecorator
     */
    public function getPatronymicAsString()
    {
        return new StringDecorator($this->getPatronymic());
    }

    /**
     * Получить email
     *
     * В mysql email - Email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->get('email');
    }

    /**
     * Получить email в декораторе String
     *
     * В mysql email - Email
     *
     * @return StringDecorator
     */
    public function getEmailAsString()
    {
        return new StringDecorator($this->getEmail());
    }

    /**
     * Получить phone
     *
     * В mysql phone - Телефон
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
     * В mysql phone - Телефон
     *
     * @return StringDecorator
     */
    public function getPhoneAsString()
    {
        return new StringDecorator($this->getPhone());
    }

    /**
     * Получить full_address
     *
     * В mysql full_address
     *
     * @return string
     */
    public function getFullAddress()
    {
        return $this->get('full_address');
    }

    /**
     * Получить full_address в декораторе String
     *
     * В mysql full_address
     *
     * @return StringDecorator
     */
    public function getFullAddressAsString()
    {
        return new StringDecorator($this->getFullAddress());
    }

    /**
     * Получить district
     *
     * В mysql district
     *
     * @return string
     */
    public function getDistrict()
    {
        return $this->get('district');
    }

    /**
     * Получить district в декораторе String
     *
     * В mysql district
     *
     * @return StringDecorator
     */
    public function getDistrictAsString()
    {
        return new StringDecorator($this->getDistrict());
    }

    /**
     * Получить region
     *
     * В mysql region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->get('region');
    }

    /**
     * Получить region в декораторе String
     *
     * В mysql region
     *
     * @return StringDecorator
     */
    public function getRegionAsString()
    {
        return new StringDecorator($this->getRegion());
    }

    /**
     * Получить identificator_first
     *
     * В mysql identificator_first
     *
     * @return string
     */
    public function getIdentificatorFirst()
    {
        return $this->get('identificator_first');
    }

    /**
     * Получить identificator_first в декораторе String
     *
     * В mysql identificator_first
     *
     * @return StringDecorator
     */
    public function getIdentificatorFirstAsString()
    {
        return new StringDecorator($this->getIdentificatorFirst());
    }

    /**
     * Получить identificator_second
     *
     * В mysql identificator_second
     *
     * @return string
     */
    public function getIdentificatorSecond()
    {
        return $this->get('identificator_second');
    }

    /**
     * Получить identificator_second в декораторе String
     *
     * В mysql identificator_second
     *
     * @return StringDecorator
     */
    public function getIdentificatorSecondAsString()
    {
        return new StringDecorator($this->getIdentificatorSecond());
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

    /**
     * Получить связанную сущность identifictor
     *
     * @return IdentifictorEntity
     */
    public function getIdentifictor()
    {
        return $this->getRelated('_identifictor');
    }

    /**
     * Получить набор связанных сущностей identifictor
     *
     * @return IdentifictorCollection|IdentifictorEntity[]
     */
    public function getIdentifictorList()
    {
        return $this->getRelated('_identifictor_list');
    }
}
