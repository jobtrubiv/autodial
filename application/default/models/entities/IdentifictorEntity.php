<?php
/**
 * Сущность
 *
 * @author Model_Generator
 */
class IdentifictorEntity extends Model_Entity_Abstract
{
    protected function _setupDataTypes()
    {
        $this->_dataTypes = array('id' => self::DATA_TYPE_INT,
            'user_id' => self::DATA_TYPE_INT,
            'identificator' => self::DATA_TYPE_STRING);
    }

    protected function _setupRelatedTypes()
    {
        $this->_relatedTypes = array('_user' => 'UserEntity');
    }

    /************************************************************************
     * Getters
     ************************************************************************/

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
     * Получить identificator
     *
     * В mysql identificator
     *
     * @return string
     */
    public function getIdentificator()
    {
        return $this->get('identificator');
    }

    /**
     * Получить identificator в декораторе String
     *
     * В mysql identificator
     *
     * @return StringDecorator
     */
    public function getIdentificatorAsString()
    {
        return new StringDecorator($this->getIdentificator());
    }

    /************************************************************************
     * Related
     ************************************************************************/

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
