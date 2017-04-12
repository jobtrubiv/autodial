<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class NumberActionEntity extends Model_Entity_Abstract
{
    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'code' => self::DATA_TYPE_STRING,
            'name' => self::DATA_TYPE_STRING,
            'create_date' => self::DATA_TYPE_STRING);
    }


    /************************************************************************
     * Getters
     ************************************************************************/

    /**
     * Получить code
     *
     * В mysql code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->get('code');
    }

    /**
     * Получить code в декораторе String
     *
     * В mysql code
     *
     * @return StringDecorator
     */
    public function getCodeAsString()
    {
        return new StringDecorator($this->getCode());
    }

    /**
     * Получить name
     *
     * В mysql name
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
     * В mysql name
     *
     * @return StringDecorator
     */
    public function getNameAsString()
    {
        return new StringDecorator($this->getName());
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

}
