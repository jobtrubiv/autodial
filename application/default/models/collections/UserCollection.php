<?php
/**
 * Набор данных
 *
 * @author Model_Generator
 */
class UserCollection extends Model_Collection_Abstract
{
    protected function _setupDefaultEntityType()
    {
        $this->_defaultEntityType = 'UserEntity';
    }
}