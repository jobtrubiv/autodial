<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class AdminEntity extends Model_Entity_Abstract
{
    public function getFullName()
    {
        return $this->getSurname() . ' ' . $this->getName() . ' ' . $this->getPatronymic();
    }

    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'type' => self::DATA_TYPE_STRING,
            'login' => self::DATA_TYPE_STRING,
            'password' => self::DATA_TYPE_STRING,
            'email' => self::DATA_TYPE_STRING,
            'surname' => self::DATA_TYPE_STRING,
            'name' => self::DATA_TYPE_STRING,
            'patronymic' => self::DATA_TYPE_STRING,
            'status' => self::DATA_TYPE_STRING,
            'create_date' => self::DATA_TYPE_STRING);
    }

    protected function _setupRelatedTypes()
    {
        $this->_relatedTypes = array('_call_file' => 'CallFileEntity',
            '_call_file_list' => 'CallFileCollection',
            '_dial_log' => 'DialLogEntity',
            '_dial_log_list' => 'DialLogCollection');
    }

    /************************************************************************
     * Getters
     ************************************************************************/

    /**
     * Получить type
     *
     * В mysql type - Тип учетной записи
     *
     * @return string
     */
    public function getType()
    {
        return $this->get('type');
    }

    /**
     * Проверить 'type' == 'administrator'
     *
     * В mysql type - Тип учетной записи
     *
     * @return boolean
     */
    public function isTypeAdministrator()
    {
        return $this->getType() == 'administrator';
    }

    /**
     * Проверить 'type' == 'operator'
     *
     * В mysql type - Тип учетной записи
     *
     * @return boolean
     */
    public function isTypeOperator()
    {
        return $this->getType() == 'operator';
    }

    /**
     * Получить login
     *
     * В mysql login - Логин
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->get('login');
    }

    /**
     * Получить login в декораторе String
     *
     * В mysql login - Логин
     *
     * @return StringDecorator
     */
    public function getLoginAsString()
    {
        return new StringDecorator($this->getLogin());
    }

    /**
     * Получить password
     *
     * В mysql password - Пароль
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->get('password');
    }

    /**
     * Получить password в декораторе String
     *
     * В mysql password - Пароль
     *
     * @return StringDecorator
     */
    public function getPasswordAsString()
    {
        return new StringDecorator($this->getPassword());
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
     * Проверить статус == 'blocked'
     *
     * В mysql status - Статус
     *
     * @return boolean
     */
    public function isBlocked()
    {
        return $this->getStatus() == 'blocked';
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

    /************************************************************************
     * Additional
     ************************************************************************/

    /**
     * Получить код авторизации админа
     *
     * @return string
     */
    public function getCode()
    {
        if (!$this->exists()) {
            return '';
        }
        return $this->getId() . '_' . $this->getPassword();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!$this->exists()) {
            return '';
        }
        return $this->getName();
    }

}
