<?php

class FunctionResult
{
    protected $_result = null;
    protected $_errors = array();

    public function __construct($result = null)
    {
        $this->setResult($result);
    }

    public function setResult($result)
    {
        $this->_result = $result;
        return $this;
    }

    public function getResult()
    {
        return $this->_result;
    }

    public function add($errorCode)
    {
        if ($errorCode instanceof FunctionResult){
            $this->_errors = array_merge($this->_errors, $errorCode->getErrors());
        }else{
            $this->_errors[$errorCode] = Zend_Registry::get('translator')->getAdapter()->translate($errorCode);
        }


        return $this;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getErrorsByString()
    {
        $message = '';
        foreach ($this->_errors as $error) {
            $message .= $error . '; ';
        }

        return $message;
    }

    public function isError()
    {
        return count($this->_errors) > 0;
    }
}