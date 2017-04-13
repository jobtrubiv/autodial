<?php

class TranslateModel
{

    public static function getTranslateMessageByCode($code)
    {
        return Zend_Registry::get('translator')->getAdapter()->translate($code);
    }

    /**
     * @return TranslateModel
     */
    public static function getInstance()
    {
        if (!self::$_instance instanceof TranslateModel) {

            self::$_instance = new self;
        }

        return self::$_instance;
    }
}