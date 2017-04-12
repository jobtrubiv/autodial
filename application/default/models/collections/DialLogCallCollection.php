<?php
/**
 * Набор данных
 *
 * @author Model_Generator
 */
class DialLogCallCollection extends Model_Collection_Abstract
{
    protected function _setupDefaultEntityType()
    {
        $this->_defaultEntityType = 'DialLogCallEntity';
    }


    public function allDuration()
    {
        $duration = 0;
        /**
         * @var DialLogCallEntity $dialLogCall
         */
        foreach ($this as $dialLogCall){
            $duration += $dialLogCall->getDuration();
        }

        return $duration;
    }
}