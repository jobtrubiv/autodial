<?php


abstract class DialLogCallModelAbstract extends Model_Db_Mysql_Abstract
{
    const WITH_DIAL_LOG = 'dial_log';
    const WITH_USER = 'user';
    const JOIN_DIAL_LOG = 'dial_log';
    const JOIN_USER = 'user';
    protected $_filterRules;
    
    protected function _setupTableName()
    {
        $this->_table = 'dial_log_call';
    }

    
    
    public function importDialLogCall($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $dialLogCallId = null;

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
            if (!$dialLogCallId && array_key_exists('id', $data)) {
                $dialLogCallId = DialLogCallModel::getInstance()->existsDialLogCallByDialLogCall($data['id']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (array_key_exists('dial_log_id', $data)) {
                $_data['dial_log_id'] = $data['dial_log_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$dialLogCallId && !array_key_exists('dial_log_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_dial_log', $data) && !empty($data['_dial_log'])) {
                if ($data['_dial_log'] instanceof Model_Entity_Interface) {
                    $data['_dial_log'] = $data['_dial_log']->toArray(true);
                }

                $_result = DialLogModel::getInstance()->importDialLog($data['_dial_log'], $importOpts->getChild('dial_log'));

                $result->addChild('dial_log', $_result);

                if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                    return $result;
                }

                if ($_result->isValid()) {
                    $data['dial_log_id'] = $_result->getResult();
                    $_data['dial_log_id'] = $_result->getResult();
                }
            }

            if (array_key_exists('user_id', $data)) {
                $_data['user_id'] = $data['user_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$dialLogCallId && !array_key_exists('user_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_user', $data) && !empty($data['_user'])) {
                if ($data['_user'] instanceof Model_Entity_Interface) {
                    $data['_user'] = $data['_user']->toArray(true);
                }

                $_result = UserModel::getInstance()->importUser($data['_user'], $importOpts->getChild('user'));

                $result->addChild('user', $_result);

                if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                    return $result;
                }

                if ($_result->isValid()) {
                    $data['user_id'] = $_result->getResult();
                    $_data['user_id'] = $_result->getResult();
                }
            }

            if (!$dialLogCallId) {
                try {
                    $_result = $this->addDialLogCall($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $dialLogCallId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import DialLogCall failed: ' . $ex->getMessage(), 'import_dial_log_call_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateDialLogCall($data, DialLogCallModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialLogCallId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateDialLogCall($_data, DialLogCallModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialLogCallId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($dialLogCallId));

            if (!$dialLogCallId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($dialLogCallId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {


            }

        }

        return $result;
    }

    
    public function importDialLogCallList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importDialLogCall($item, $importOpts);
                $result->addChild('dial_log_call', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addDialLogCall($dialLogCall)
    {
        $dialLogCallId = null;
        $dialLogCall = new DialLogCallEntity($dialLogCall);
        $dialLogCallData = $dialLogCall->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $dialLogCallData = $this->addDialLogCallFilter($dialLogCallData);

        $validator = $this->addDialLogCallValidate($dialLogCallData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $dialLogCallId = $this->insert($this->getTable(), $dialLogCallData);

                if (!$dialLogCallId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add DialLogCall failed', 'add_dialLogCall_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($dialLogCallId))
               ->setValidator($validator);
               
        return $result;
    }

    
    public function getFilterRules()
    {
        if ($this->_filterRules != null) {
            return $this->_filterRules;
        }
        
        $this->_filterRules = array(
            'dial_log_id' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'user_id' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'phone' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'duration' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'call_digit' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'status' => array(
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
            'dial_log_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'dial_log_id' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
                new Zend_Validate_Db_RecordExists(array('adapter' => $this->getDb(), 'table' => 'dial_log', 'field' => 'id')),  // Существование связи
            ),
            'user_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'user_id' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
                new Zend_Validate_Db_RecordExists(array('adapter' => $this->getDb(), 'table' => 'user', 'field' => 'id')),  // Существование связи
            ),
            'phone' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'phone' в $data
                new Zend_Validate_StringLength(0, 50, 'UTF-8'),  // Проверяем строку
            ),
            'duration' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
            ),
            'call_digit' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
            ),
            'status' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'status' в $data
                new Zend_Validate_InArray(array('busy', 'answered', 'no_answered', 'failed')),  // Проверяем на вхождение
            ),
            'create_date' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Date(array('format' => 'Y-m-d H:i:s')),  // Проверяем дату
            ),

        );

        return $validators;
    }

    
    public function addDialLogCallFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialLogCall
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'dial_log_id',
                'user_id',
                'phone',
                'duration',
                'call_digit',
                'status',
                'create_date' => date('Y-m-d H:i:s'),

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addDialLogCallValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        $dialLogCall = new DialLogCallEntity($dialLogCall);
        $dialLogCallData = $dialLogCall->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $dialLogCallData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update DialLogCall failed', 'update_dialLogCall_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogCallData['id']));
                unset($dialLogCallData['id']);
            }
        }

        // Фильтруем данные
        $dialLogCallData = $this->updateDialLogCallFilter($dialLogCallData);

        $validator = $this->updateDialLogCallValidate($dialLogCallData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $dialLogCallData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateDialLogCallByDialLogCall($dialLogCall, $dialLogCallData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $dialLogCallIds = $this->getDialLogCallIdsFromMixed($dialLogCall);
        if (!$dialLogCallIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update DialLogCall failed', 'update_dialLogCall_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogCallIds));
        
        return $this->updateDialLogCall($dialLogCallData, $opts);
    }

    
    public function updateDialLogCallFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialLogCall
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'dial_log_id',
                'user_id',
                'phone',
                'duration',
                'call_digit',
                'status',
                'create_date',

        );

        $_data = $this->getDataValues($data, $defaults);


        if (empty($_data)) {
            return array();
        }
        
        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();

        return $_data;
    }

    
    public function updateDialLogCallValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteDialLogCall(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateDialLogCall
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteDialLogCall(Model_Cond $opts = null)
    {
        return $this->_deleteDialLogCall($opts);
    }

    
    public function deleteDialLogCallByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $dialLogCall и берем первый
        $dialLogCallIds = $this->getDialLogCallIdsFromMixed($dialLogCall);

        if (!empty($dialLogCallIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogCallIds));

            $this->_deleteDialLogCall($opts);
        }
    }

    
    public function truncateDialLogCall()
    {
        $this->truncate();
    }

    
    
    protected function _getDialLogCall(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $opts->from($this->getTable());

        if ($opts->checkAnyJoin()) {
            $joinList = $opts->getJoin();
            foreach ($joinList as $join) {
                if ($join->issetRule()) {
                    continue;
                }

                $joinEntity = $join->getEntity();
                if ($joinEntity == self::JOIN_DIAL_LOG) {
                    $join->setRule('dial_log',
                                    $this->getTableWithColumnQuoted('dial_log_id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('id', null, 'dial_log'),
                                    '');
                    continue;
                }

                if ($joinEntity == self::JOIN_USER) {
                    $join->setRule('user',
                                    $this->getTableWithColumnQuoted('user_id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('id', null, 'user'),
                                    '');
                    continue;
                }

            }
        }


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getDialLogCall(Model_Cond $opts = null)
    {
        return $this->_getDialLogCall($opts);
    }

    
    public function getDialLogCallByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogCallIds = $this->getDialLogCallIdsFromMixed($dialLogCall);
        if (empty($dialLogCallIds) || (count($dialLogCallIds) == 1 && reset($dialLogCallIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogCallIds));

        return $this->_getDialLogCall($opts);
    }

    
    public function getDialLogCallList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getDialLogCall($opts->type(self::FETCH_ALL));
    }

    
    public function getDialLogCallListByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogCallByDialLogCall($dialLogCall, $opts->type(self::FETCH_ALL));
    }

    
    public function getDialLogCallCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getDialLogCall
        return $this->_getDialLogCall($opts->type(self::FETCH_COUNT));
    }

    
    public function existsDialLogCallByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogCallIds = $this->getDialLogCallIdsFromMixed($dialLogCall);
        if (empty($dialLogCallIds) || (count($dialLogCallIds) == 1 && reset($dialLogCallIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogCallIds));

        return $this->_getDialLogCall($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialLogCallByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogIds = DialLogModel::getInstance()->getDialLogIdsFromMixed($dialLog);
        if (empty($dialLogIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('dial_log_id') => $dialLogIds));

        return $this->_getDialLogCall($opts);
    }

    
    public function getDialLogCallListByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogCallByDialLog($dialLog, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialLogCallByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogCallByDialLog($dialLog, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialLogCallCountByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogCallByDialLog($dialLog, $opts->type(self::FETCH_COUNT));
    }

    
    public function getDialLogCallByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $userIds = UserModel::getInstance()->getUserIdsFromMixed($user);
        if (empty($userIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('user_id') => $userIds));

        return $this->_getDialLogCall($opts);
    }

    
    public function getDialLogCallListByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogCallByUser($user, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialLogCallByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogCallByUser($user, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialLogCallCountByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogCallByUser($user, $opts->type(self::FETCH_COUNT));
    }


    



    
    
    public function prepareDialLogCall($data, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_ENTITY, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }
        
        if (!empty($data)) {
            if ($opts->checkWith(self::WITH_DIAL_LOG)) {
                $data['_' . self::WITH_DIAL_LOG] = DialLogModel::getInstance()->getDialLogByDialLog($data['dial_log_id'], $opts->getWith(self::WITH_DIAL_LOG)->setEntity('dial_log'));
            }

            if ($opts->checkWith(self::WITH_USER)) {
                $data['_' . self::WITH_USER] = UserModel::getInstance()->getUserByUser($data['user_id'], $opts->getWith(self::WITH_USER)->setEntity('user'));
            }

     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new DialLogCallEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareDialLogCallList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareDialLogCall($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new DialLogCallCollection($data);
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

    
    
    public function getDialLogCallIdsFromMixed($dialLogCall)
    {
        if (is_object($dialLogCall)
            && !$dialLogCall instanceof DialLogCallEntity
            && !$dialLogCall instanceof DialLogCallCollection
            && !$dialLogCall instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($dialLogCall);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
