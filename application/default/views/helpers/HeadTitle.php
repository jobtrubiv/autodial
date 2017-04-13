<?php

class App_View_Helper_HeadTitle extends Zend_View_Helper_HeadTitle
{
    public function headTitle($title = null, $setType = Zend_View_Helper_Placeholder_Container_Abstract::PREPEND)
    {
        if ($title !== null) {
            $title = $this->view->translate($title);
        }
        return parent::headTitle($title, $setType);
    }
}