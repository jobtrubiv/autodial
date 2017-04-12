<?php


abstract class NumberActionModelAbstract extends Model_Db_Mysql_Abstract
{
    protected $_filterRules;

    protected function _setupTableName()
    {
        $this->_table = 'number_action';
    }

    
    
    public function importNumberAction($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $numberActionId = null;

        $result = new Model_Result();

        $_data = array();

        if ($data instanceof Model_Entity_Interface) {
            $data = $data->toArray(true);
        }

        if (empty($data)) {
            $result->addChild('general', $this->getGeneralErrorResult('Import Package failed: import data is empty', 'import_package_failed_data_empty'));
            return $result;
        }

        if (is_array($data)) {
            if (!$numberActionId && array_key_exists('id', $data)) {
                $numberActionId = NumberActionModel::getInstance()->existsNumberActionByNumberAction($data['id']);
            }

            if (!$numberActionId && array_key_exists('code', $data)) {
                $numberActionId = NumberActionModel::getInstance()->existsNumberActionByCode($data['code']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (!$numberActionId) {
                try {
                    $_result = $this->addNumberAction($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $numberActionId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import NumberAction failed: ' . $ex->getMessage(), 'import_number_action_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateNumberAction($data, NumberActionModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $numberActionId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateNumberAction($_data, NumberActionModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $numberActionId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($numberActionId));

            if (!$numberActionId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($numberActionId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {


            }

        }

        return $result;
    }

    
    public function importNumberActionList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importNumberAction($item, $importOpts);
                $result->addChild('number_action', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addNumberAction($numberAction)
    {
        $numberActionId = null;
        $numberAction = new NumberActionEntity($numberAction);
        $numberActionData = $numberAction->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $numberActionData = $this->addNumberActionFilter($numberActionData);

        $validator = $this->addNumberActionValidate($numberActionData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $numberActionId = $this->insert($this->getTable(), $numberActionData);

                if (!$numberActionId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add NumberAction failed', 'add_numberAction_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($numberActionId))
               ->setValidator($validator);
               
        return $result;
    }

    
    public function getFilterRules()
    {
        if ($this->_filterRules != null) {
            return $this->_filterRules;
        }
        
        $this->_filterRules = array(
            'code' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'name' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'create_date' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),

        );

        return $this->_filterRules;
    }

    
    protected function getValidatorRules($optionalPresence = false)
    {
        $presence = $optionalPresence ? Zend_Filter_Input::PRESENCE_OPTIONAL : null;

        $validators = array(
            'code' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'code' в $data
                new Zend_Validate_StringLength(0, 100, 'UTF-8'),  // Проверяем строку
            ),
            'name' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'name' в $data
                new Zend_Validate_StringLength(0, 65535, 'UTF-8'),  // Проверяем строку
            ),
            'create_date' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Date(array('format' => 'Y-m-d H:i:s')),  // Проверяем дату
            ),

        );

        return $validators;
    }

    
    public function addNumberActionFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $numberAction
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'code',
                'name',
                'create_date' => date('Y-m-d H:i:s'),

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addNumberActionValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateNumberAction($numberAction, Model_Cond $opts = null)
    {
        $numberAction = new NumberActionEntity($numberAction);
        $numberActionData = $numberAction->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $numberActionData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update NumberAction failed', 'update_numberAction_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $numberActionData['id']));
                unset($numberActionData['id']);
            }
        }

        // Фильтруем данные
        $numberActionData = $this->updateNumberActionFilter($numberActionData);

        $validator = $this->updateNumberActionValidate($numberActionData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $numberActionData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateNumberActionByNumberAction($numberAction, $numberActionData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $numberActionIds = $this->getNumberActionIdsFromMixed($numberAction);
        if (!$numberActionIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update NumberAction failed', 'update_numberAction_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $numberActionIds));
        
        return $this->updateNumberAction($numberActionData, $opts);
    }

    
    public function updateNumberActionFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $numberAction
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'code',
                'name',
                'create_date',

        );

        $_data = $this->getDataValues($data, $defaults);


        if (empty($_data)) {
            return array();
        }
        
        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();

        return $_data;
    }

    
    public function updateNumberActionValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteNumberAction(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateNumberAction
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteNumberAction(Model_Cond $opts = null)
    {
        return $this->_deleteNumberAction($opts);
    }

    
    public function deleteNumberActionByNumberAction($numberAction, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $numberAction и берем первый
        $numberActionIds = $this->getNumberActionIdsFromMixed($numberAction);

        if (!empty($numberActionIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $numberActionIds));

            $this->_deleteNumberAction($opts);
        }
    }

    
    public function truncateNumberAction()
    {
        $this->truncate();
    }

    
    
    protected function _getNumberAction(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $opts->from($this->getTable());


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getNumberAction(Model_Cond $opts = null)
    {
        return $this->_getNumberAction($opts);
    }

    
    public function getNumberActionByNumberAction($numberAction, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $numberActionIds = $this->getNumberActionIdsFromMixed($numberAction);
        if (empty($numberActionIds) || (count($numberActionIds) == 1 && reset($numberActionIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $numberActionIds));

        return $this->_getNumberAction($opts);
    }

    
    public function getNumberActionList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getNumberAction($opts->type(self::FETCH_ALL));
    }

    
    public function getNumberActionListByNumberAction($numberAction, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getNumberActionByNumberAction($numberAction, $opts->type(self::FETCH_ALL));
    }

    
    public function getNumberActionCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getNumberAction
        return $this->_getNumberAction($opts->type(self::FETCH_COUNT));
    }

    
    public function existsNumberActionByNumberAction($numberAction, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $numberActionIds = $this->getNumberActionIdsFromMixed($numberAction);
        if (empty($numberActionIds) || (count($numberActionIds) == 1 && reset($numberActionIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $numberActionIds));

        return $this->_getNumberAction($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getNumberActionByCode($code, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $codeIds = $this->_getIdsFromMixed($code, 'strval');

        if (empty($codeIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('code') => $codeIds));

        return $this->getNumberAction($opts);
    }

    
    public function existsNumberActionByCode($code, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getNumberActionByCode($code, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }


    



    
    
    public function prepareNumberAction($data, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_ENTITY, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }
        
        if (!empty($data)) {
     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new NumberActionEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareNumberActionList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareNumberAction($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new NumberActionCollection($data);
                $result->setPager($pager);
                return $result;
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                $result = new $returnType($data);
                if ($result instanceof Model_Collection_Interface) {
                    $result->setPager($pager);
                }
                return $result;
        }
    }

    
    
    public function getNumberActionIdsFromMixed($numberAction)
    {
        if (is_object($numberAction)
            && !$numberAction instanceof NumberActionEntity
            && !$numberAction instanceof NumberActionCollection
            && !$numberAction instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($numberAction);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
