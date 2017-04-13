<?php

class App_View_Helper_InfoUrl extends Zend_View_Helper_Abstract
{
    function infoUrl($entity, $simpleLayout = false)
    {
        $url = $this->view->url($simpleLayout ? 'front-staff_info_search' : 'log_movement_search', array('search' => strval($entity)));
        $url = preg_replace("/\/page1$/i", '', $url);
        return $url;
    }
}