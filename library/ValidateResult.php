<?php

class ValidateResult
{
    private $_dataArray;
    private $_errorArray;

    public function  __construct($dataArray = array())
    {
        $this->_dataArray = $dataArray;
    }

    public function getErrorArray()
    {
        return $this->_errorArray;
    }

    public function isFieldError($param)
    {
        return isset($this->_errorArray[$param]);
    }

    public function isError()
    {
        return count($this->_errorArray) != 0;
    }

    public function addError($filed, $errorMessage)
    {
        $this->_errorArray[$filed] = $errorMessage;
    }

    public function addErrorArray($array)
    {
        if (count($array) == 0){
            return;
        }

        foreach ($array as $field => $message){
            $this->addError($field, $message);
        }
    }

    public function getError($filed, $subArray = '')
    {
        if ($subArray){
            if (!isset($this->_errorArray[$subArray][$filed])){
                return '';
            }

            $data = $this->_errorArray[$subArray][$filed];

        }else{
            if (!isset($this->_errorArray[$filed])){
                return '';
            }

            $data = $this->_errorArray[$filed];
        }

        return $data;
    }

    public function getData($filed, $subArray = '')
    {
        if ($subArray){
            if (!isset($this->_dataArray[$subArray][$filed])){
                return '';
            }

            $data = $this->_dataArray[$subArray][$filed];

        }else{
            if (!isset($this->_dataArray[$filed])){
                return '';
            }

            $data = $this->_dataArray[$filed];
        }

        return $data;
    }

    public function isData()
    {
        return count($this->_dataArray) != 0;
    }
}