<?php


abstract class DialLogModelAbstract extends Model_Db_Mysql_Abstract
{
    const WITH_ADMIN = 'admin';
    const WITH_DIAL_RULE = 'dial_rule';
    const WITH_DIAL_LOG_CALL = 'dial_log_call';
    const WITH_DIAL_LOG_CALL_LIST = 'dial_log_call_list';
    const JOIN_ADMIN = 'admin';
    const JOIN_DIAL_RULE = 'dial_rule';
    const JOIN_DIAL_LOG_CALL = 'dial_log_call';
    protected $_filterRules;

    protected function _setupTableName()
    {
        $this->_table = 'dial_log';
    }
    
    public function importDialLog($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $dialLogId = null;

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
            if (!$dialLogId && array_key_exists('id', $data)) {
                $dialLogId = DialLogModel::getInstance()->existsDialLogByDialLog($data['id']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (array_key_exists('admin_id', $data)) {
                $_data['admin_id'] = $data['admin_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$dialLogId && !array_key_exists('admin_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_admin', $data) && !empty($data['_admin'])) {
                if ($data['_admin'] instanceof Model_Entity_Interface) {
                    $data['_admin'] = $data['_admin']->toArray(true);
                }

                $_result = AdminModel::getInstance()->importAdmin($data['_admin'], $importOpts->getChild('admin'));

                $result->addChild('admin', $_result);

                if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                    return $result;
                }

                if ($_result->isValid()) {
                    $data['admin_id'] = $_result->getResult();
                    $_data['admin_id'] = $_result->getResult();
                }
            }

            if (array_key_exists('dial_rule_id', $data)) {
                $_data['dial_rule_id'] = $data['dial_rule_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$dialLogId && !array_key_exists('dial_rule_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_dial_rule', $data) && !empty($data['_dial_rule'])) {
                if ($data['_dial_rule'] instanceof Model_Entity_Interface) {
                    $data['_dial_rule'] = $data['_dial_rule']->toArray(true);
                }

                $_result = DialRuleModel::getInstance()->importDialRule($data['_dial_rule'], $importOpts->getChild('dial_rule'));

                $result->addChild('dial_rule', $_result);

                if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                    return $result;
                }

                if ($_result->isValid()) {
                    $data['dial_rule_id'] = $_result->getResult();
                    $_data['dial_rule_id'] = $_result->getResult();
                }
            }

            if (!$dialLogId) {
                try {
                    $_result = $this->addDialLog($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $dialLogId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import DialLog failed: ' . $ex->getMessage(), 'import_dial_log_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateDialLog($data, DialLogModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialLogId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateDialLog($_data, DialLogModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialLogId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($dialLogId));

            if (!$dialLogId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($dialLogId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {

                if (isset($data['_dial_log_call']) && (is_array($data['_dial_log_call']) || $data['_dial_log_call'] instanceof Model_Entity_Interface)) {
                    if ($data['_dial_log_call'] instanceof Model_Entity_Interface) {
                        $data['_dial_log_call'] = $data['_dial_log_call']->toArray(true);
                    }

                    if ($dialLogId) {
                        $data['_dial_log_call']['dial_log_id'] = $dialLogId;
                    }

                    $_result = new Model_Result();
                    if ($dialLogId && !$importOpts->getChild('dial_log_call')->getAppendLink()) {
                        $opts = DialLogCallModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_log_id', null, 'dial_log_call') . ' = ?', $dialLogId);
                        DialLogCallModel::getInstance()->deleteDialLogCall($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_dial_log_call'])) {
                            $result->addChild('dial_log_call', new Model_Result());
                        } else {
                            $_result = DialLogCallModel::getInstance()->importDialLogCall($data['_dial_log_call'], $importOpts->getChild('dial_log_call'));
                            $result->addChild('dial_log_call', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_dial_log_call_list']) && (is_array($data['_dial_log_call_list']) || $data['_dial_log_call_list'] instanceof Model_Collection_Interface)) {
                    if ($data['_dial_log_call_list'] instanceof Model_Collection_Interface) {
                        $data['_dial_log_call_list'] = $data['_dial_log_call_list']->toArray(true);
                    }

                    if ($dialLogId && !empty($data['_dial_log_call_list'])) {
                        foreach ($data['_dial_log_call_list'] as &$item) {
                            if (is_array($item)) {
                                $item['dial_log_id'] = $dialLogId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($dialLogId && !$importOpts->getChild('dial_log_call_list')->getAppendLink()) {
                        $opts = DialLogCallModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_log_id', null, 'dial_log_call') . ' = ?', $dialLogId);
                        DialLogCallModel::getInstance()->deleteDialLogCall($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_dial_log_call_list'])) {
                            $result->addChild('dial_log_call_list', new Model_Result());
                        } else {
                            $_result = DialLogCallModel::getInstance()->importDialLogCallList($data['_dial_log_call_list'], $importOpts->getChild('dial_log_call_list'));
                            $result->addChild('dial_log_call_list', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }


            }

        }

        return $result;
    }

    
    public function importDialLogList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importDialLog($item, $importOpts);
                $result->addChild('dial_log', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addDialLog($dialLog)
    {
        $dialLogId = null;
        $dialLog = new DialLogEntity($dialLog);
        $dialLogData = $dialLog->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $dialLogData = $this->addDialLogFilter($dialLogData);

        $validator = $this->addDialLogValidate($dialLogData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $dialLogId = $this->insert($this->getTable(), $dialLogData);

                if (!$dialLogId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add DialLog failed', 'add_dialLog_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($dialLogId))
               ->setValidator($validator);
               
        return $result;
    }

    
    public function getFilterRules()
    {
        if ($this->_filterRules != null) {
            return $this->_filterRules;
        }
        
        $this->_filterRules = array(
            'admin_id' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'dial_rule_id' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'status' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'error' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'hash' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
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
            'admin_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'admin_id' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
                new Zend_Validate_Db_RecordExists(array('adapter' => $this->getDb(), 'table' => 'admin', 'field' => 'id')),  // Существование связи
            ),
            'dial_rule_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'dial_rule_id' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
                new Zend_Validate_Db_RecordExists(array('adapter' => $this->getDb(), 'table' => 'dial_rule', 'field' => 'id')),  // Существование связи
            ),
            'status' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_InArray(array('success', 'failed', 'canceled')),  // Проверяем на вхождение
            ),
            'error' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 65535, 'UTF-8'),  // Проверяем строку
            ),
            'hash' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'hash' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
            ),
            'create_date' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Date(array('format' => 'Y-m-d H:i:s')),  // Проверяем дату
            ),

        );

        return $validators;
    }

    
    public function addDialLogFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialLog
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'admin_id',
                'dial_rule_id',
                'status',
                'error',
                'hash',
                'create_date' => date('Y-m-d H:i:s'),

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addDialLogValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateDialLog($dialLog, Model_Cond $opts = null)
    {
        $dialLog = new DialLogEntity($dialLog);
        $dialLogData = $dialLog->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $dialLogData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update DialLog failed', 'update_dialLog_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogData['id']));
                unset($dialLogData['id']);
            }
        }

        // Фильтруем данные
        $dialLogData = $this->updateDialLogFilter($dialLogData);

        $validator = $this->updateDialLogValidate($dialLogData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $dialLogData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateDialLogByDialLog($dialLog, $dialLogData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $dialLogIds = $this->getDialLogIdsFromMixed($dialLog);
        if (!$dialLogIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update DialLog failed', 'update_dialLog_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogIds));
        
        return $this->updateDialLog($dialLogData, $opts);
    }

    
    public function updateDialLogFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialLog
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'admin_id',
                'dial_rule_id',
                'status',
                'error',
                'hash',
                'create_date',

        );

        $_data = $this->getDataValues($data, $defaults);


        if (empty($_data)) {
            return array();
        }
        
        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();

        return $_data;
    }

    
    public function updateDialLogValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteDialLog(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateDialLog
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteDialLog(Model_Cond $opts = null)
    {
        return $this->_deleteDialLog($opts);
    }

    
    public function deleteDialLogByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $dialLog и берем первый
        $dialLogIds = $this->getDialLogIdsFromMixed($dialLog);

        if (!empty($dialLogIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogIds));

            $this->_deleteDialLog($opts);
        }
    }

    
    public function truncateDialLog()
    {
        $this->truncate();
    }

    
    
    protected function _getDialLog(Model_Cond $opts = null)
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
                if ($joinEntity == self::JOIN_ADMIN) {
                    $join->setRule('admin',
                                    $this->getTableWithColumnQuoted('admin_id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('id', null, 'admin'),
                                    '');
                    continue;
                }

                if ($joinEntity == self::JOIN_DIAL_RULE) {
                    $join->setRule('dial_rule',
                                    $this->getTableWithColumnQuoted('dial_rule_id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('id', null, 'dial_rule'),
                                    '');
                    continue;
                }

                if ($joinEntity == self::JOIN_DIAL_LOG_CALL) {
                    $join->setRule('dial_log_call',
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('dial_log_id', null, 'dial_log_call'),
                                    '');
                    continue;
                }

            }
        }


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getDialLog(Model_Cond $opts = null)
    {
        return $this->_getDialLog($opts);
    }

    
    public function getDialLogByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogIds = $this->getDialLogIdsFromMixed($dialLog);
        if (empty($dialLogIds) || (count($dialLogIds) == 1 && reset($dialLogIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogIds));

        return $this->_getDialLog($opts);
    }

    
    public function getDialLogList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getDialLog($opts->type(self::FETCH_ALL));
    }

    
    public function getDialLogListByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByDialLog($dialLog, $opts->type(self::FETCH_ALL));
    }

    
    public function getDialLogCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getDialLog
        return $this->_getDialLog($opts->type(self::FETCH_COUNT));
    }

    
    public function existsDialLogByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogIds = $this->getDialLogIdsFromMixed($dialLog);
        if (empty($dialLogIds) || (count($dialLogIds) == 1 && reset($dialLogIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogIds));

        return $this->_getDialLog($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialLogByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $adminIds = AdminModel::getInstance()->getAdminIdsFromMixed($admin);
        if (empty($adminIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('admin_id') => $adminIds));

        return $this->_getDialLog($opts);
    }

    
    public function getDialLogListByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByAdmin($admin, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialLogByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByAdmin($admin, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialLogCountByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByAdmin($admin, $opts->type(self::FETCH_COUNT));
    }

    
    public function getDialLogByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleIds = DialRuleModel::getInstance()->getDialRuleIdsFromMixed($dialRule);
        if (empty($dialRuleIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('dial_rule_id') => $dialRuleIds));

        return $this->_getDialLog($opts);
    }

    
    public function getDialLogListByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByDialRule($dialRule, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialLogByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByDialRule($dialRule, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialLogCountByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByDialRule($dialRule, $opts->type(self::FETCH_COUNT));
    }

    
    public function getDialLogByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogCallList = DialLogCallModel::getInstance()->getDialLogCallListByDialLogCall($dialLogCall);

        $dialLogIds = array();
        foreach($dialLogCallList as $dialLogCall) {
            $dialLogIds[] = $dialLogCall->getDialLogId();
        }

        $dialLogIds = $this->getDialLogIdsFromMixed($dialLogIds);
        if (empty($dialLogIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialLogIds));

        return $this->_getDialLog($opts);
    }

    
    public function getDialLogListByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByDialLogCall($dialLogCall, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialLogByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByDialLogCall($dialLogCall, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialLogCountByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialLogByDialLogCall($dialLogCall, $opts->type(self::FETCH_COUNT));
    }


    



    
    
    public function prepareDialLog($data, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_ENTITY, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }
        
        if (!empty($data)) {
            if ($opts->checkWith(self::WITH_ADMIN)) {
                $data['_' . self::WITH_ADMIN] = AdminModel::getInstance()->getAdminByAdmin($data['admin_id'], $opts->getWith(self::WITH_ADMIN)->setEntity('admin'));
            }

            if ($opts->checkWith(self::WITH_DIAL_RULE)) {
                $data['_' . self::WITH_DIAL_RULE] = DialRuleModel::getInstance()->getDialRuleByDialRule($data['dial_rule_id'], $opts->getWith(self::WITH_DIAL_RULE)->setEntity('dial_rule'));
            }

            if ($opts->checkWith(self::WITH_DIAL_LOG_CALL)) {
                $data['_' . self::WITH_DIAL_LOG_CALL] = DialLogCallModel::getInstance()->getDialLogCallByDialLog($data['id'], $opts->getWith(self::WITH_DIAL_LOG_CALL)->setEntity('dial_log_call'));
            }

            if ($opts->checkWith(self::WITH_DIAL_LOG_CALL_LIST)) {
                $data['_' . self::WITH_DIAL_LOG_CALL_LIST] = DialLogCallModel::getInstance()->getDialLogCallListByDialLog($data['id'], $opts->getWith(self::WITH_DIAL_LOG_CALL_LIST)->setEntity('dial_log_call'));
            }

     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new DialLogEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareDialLogList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareDialLog($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new DialLogCollection($data);
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

    
    
    public function getDialLogIdsFromMixed($dialLog)
    {
        if (is_object($dialLog)
            && !$dialLog instanceof DialLogEntity
            && !$dialLog instanceof DialLogCollection
            && !$dialLog instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($dialLog);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
