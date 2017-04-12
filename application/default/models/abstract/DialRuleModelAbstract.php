<?php


abstract class DialRuleModelAbstract extends Model_Db_Mysql_Abstract
{

    const WITH_CALL_FILE = 'call_file';
    const WITH_CALL_FILE_LIST = 'call_file_list';
    const WITH_DIAL_LOG = 'dial_log';
    const WITH_DIAL_LOG_LIST = 'dial_log_list';
    const WITH_DIAL_RULE_PARAMETR = 'dial_rule_parametr';
    const WITH_DIAL_RULE_PARAMETR_LIST = 'dial_rule_parametr_list';
    const WITH_USER = 'user';
    const WITH_USER_LIST = 'user_list';
    const JOIN_CALL_FILE = 'call_file';
    const JOIN_DIAL_LOG = 'dial_log';
    const JOIN_DIAL_RULE_PARAMETR = 'dial_rule_parametr';
    const JOIN_USER = 'user';
    protected $_filterRules;

    protected function _setupTableName()
    {
        $this->_table = 'dial_rule';
    }

    
    
    public function importDialRule($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) { $importOpts = new Model_Import_Cond();
        }

        $dialRuleId = null;

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
            if (!$dialRuleId && array_key_exists('id', $data)) {
                $dialRuleId = DialRuleModel::getInstance()->existsDialRuleByDialRule($data['id']);
            }

            if (!$dialRuleId && array_key_exists('name', $data)) {
                $dialRuleId = DialRuleModel::getInstance()->existsDialRuleByName($data['name']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (!$dialRuleId) {
                try {
                    $_result = $this->addDialRule($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $dialRuleId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import DialRule failed: ' . $ex->getMessage(), 'import_dial_rule_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateDialRule($data, DialRuleModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialRuleId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateDialRule($_data, DialRuleModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialRuleId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($dialRuleId));

            if (!$dialRuleId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($dialRuleId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {

                if (isset($data['_call_file']) && (is_array($data['_call_file']) || $data['_call_file'] instanceof Model_Entity_Interface)) {
                    if ($data['_call_file'] instanceof Model_Entity_Interface) {
                        $data['_call_file'] = $data['_call_file']->toArray(true);
                    }

                    if ($dialRuleId) {
                        $data['_call_file']['dial_rule_id'] = $dialRuleId;
                    }

                    $_result = new Model_Result();
                    if ($dialRuleId && !$importOpts->getChild('call_file')->getAppendLink()) {
                        $opts = CallFileModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_rule_id', null, 'call_file') . ' = ?', $dialRuleId);
                        CallFileModel::getInstance()->deleteCallFile($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_call_file'])) {
                            $result->addChild('call_file', new Model_Result());
                        } else {
                            $_result = CallFileModel::getInstance()->importCallFile($data['_call_file'], $importOpts->getChild('call_file'));
                            $result->addChild('call_file', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_call_file_list']) && (is_array($data['_call_file_list']) || $data['_call_file_list'] instanceof Model_Collection_Interface)) {
                    if ($data['_call_file_list'] instanceof Model_Collection_Interface) {
                        $data['_call_file_list'] = $data['_call_file_list']->toArray(true);
                    }

                    if ($dialRuleId && !empty($data['_call_file_list'])) {
                        foreach ($data['_call_file_list'] as &$item) {
                            if (is_array($item)) {
                                $item['dial_rule_id'] = $dialRuleId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($dialRuleId && !$importOpts->getChild('call_file_list')->getAppendLink()) {
                        $opts = CallFileModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_rule_id', null, 'call_file') . ' = ?', $dialRuleId);
                        CallFileModel::getInstance()->deleteCallFile($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_call_file_list'])) {
                            $result->addChild('call_file_list', new Model_Result());
                        } else {
                            $_result = CallFileModel::getInstance()->importCallFileList($data['_call_file_list'], $importOpts->getChild('call_file_list'));
                            $result->addChild('call_file_list', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_dial_log']) && (is_array($data['_dial_log']) || $data['_dial_log'] instanceof Model_Entity_Interface)) {
                    if ($data['_dial_log'] instanceof Model_Entity_Interface) {
                        $data['_dial_log'] = $data['_dial_log']->toArray(true);
                    }

                    if ($dialRuleId) {
                        $data['_dial_log']['dial_rule_id'] = $dialRuleId;
                    }

                    $_result = new Model_Result();
                    if ($dialRuleId && !$importOpts->getChild('dial_log')->getAppendLink()) {
                        $opts = DialLogModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_rule_id', null, 'dial_log') . ' = ?', $dialRuleId);
                        DialLogModel::getInstance()->deleteDialLog($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_dial_log'])) {
                            $result->addChild('dial_log', new Model_Result());
                        } else {
                            $_result = DialLogModel::getInstance()->importDialLog($data['_dial_log'], $importOpts->getChild('dial_log'));
                            $result->addChild('dial_log', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_dial_log_list']) && (is_array($data['_dial_log_list']) || $data['_dial_log_list'] instanceof Model_Collection_Interface)) {
                    if ($data['_dial_log_list'] instanceof Model_Collection_Interface) {
                        $data['_dial_log_list'] = $data['_dial_log_list']->toArray(true);
                    }

                    if ($dialRuleId && !empty($data['_dial_log_list'])) {
                        foreach ($data['_dial_log_list'] as &$item) {
                            if (is_array($item)) {
                                $item['dial_rule_id'] = $dialRuleId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($dialRuleId && !$importOpts->getChild('dial_log_list')->getAppendLink()) {
                        $opts = DialLogModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_rule_id', null, 'dial_log') . ' = ?', $dialRuleId);
                        DialLogModel::getInstance()->deleteDialLog($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_dial_log_list'])) {
                            $result->addChild('dial_log_list', new Model_Result());
                        } else {
                            $_result = DialLogModel::getInstance()->importDialLogList($data['_dial_log_list'], $importOpts->getChild('dial_log_list'));
                            $result->addChild('dial_log_list', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_dial_rule_parametr']) && (is_array($data['_dial_rule_parametr']) || $data['_dial_rule_parametr'] instanceof Model_Entity_Interface)) {
                    if ($data['_dial_rule_parametr'] instanceof Model_Entity_Interface) {
                        $data['_dial_rule_parametr'] = $data['_dial_rule_parametr']->toArray(true);
                    }

                    if ($dialRuleId) {
                        $data['_dial_rule_parametr']['dial_rule_id'] = $dialRuleId;
                    }

                    $_result = new Model_Result();
                    if ($dialRuleId && !$importOpts->getChild('dial_rule_parametr')->getAppendLink()) {
                        $opts = DialRuleParametrModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_rule_id', null, 'dial_rule_parametr') . ' = ?', $dialRuleId);
                        DialRuleParametrModel::getInstance()->deleteDialRuleParametr($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_dial_rule_parametr'])) {
                            $result->addChild('dial_rule_parametr', new Model_Result());
                        } else {
                            $_result = DialRuleParametrModel::getInstance()->importDialRuleParametr($data['_dial_rule_parametr'], $importOpts->getChild('dial_rule_parametr'));
                            $result->addChild('dial_rule_parametr', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_dial_rule_parametr_list']) && (is_array($data['_dial_rule_parametr_list']) || $data['_dial_rule_parametr_list'] instanceof Model_Collection_Interface)) {
                    if ($data['_dial_rule_parametr_list'] instanceof Model_Collection_Interface) {
                        $data['_dial_rule_parametr_list'] = $data['_dial_rule_parametr_list']->toArray(true);
                    }

                    if ($dialRuleId && !empty($data['_dial_rule_parametr_list'])) {
                        foreach ($data['_dial_rule_parametr_list'] as &$item) {
                            if (is_array($item)) {
                                $item['dial_rule_id'] = $dialRuleId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($dialRuleId && !$importOpts->getChild('dial_rule_parametr_list')->getAppendLink()) {
                        $opts = DialRuleParametrModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_rule_id', null, 'dial_rule_parametr') . ' = ?', $dialRuleId);
                        DialRuleParametrModel::getInstance()->deleteDialRuleParametr($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_dial_rule_parametr_list'])) {
                            $result->addChild('dial_rule_parametr_list', new Model_Result());
                        } else {
                            $_result = DialRuleParametrModel::getInstance()->importDialRuleParametrList($data['_dial_rule_parametr_list'], $importOpts->getChild('dial_rule_parametr_list'));
                            $result->addChild('dial_rule_parametr_list', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_user']) && (is_array($data['_user']) || $data['_user'] instanceof Model_Entity_Interface)) {
                    if ($data['_user'] instanceof Model_Entity_Interface) {
                        $data['_user'] = $data['_user']->toArray(true);
                    }

                    if ($dialRuleId) {
                        $data['_user']['dial_rule_id'] = $dialRuleId;
                    }

                    $_result = new Model_Result();
                    if ($dialRuleId && !$importOpts->getChild('user')->getAppendLink()) {
                        $opts = UserModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_rule_id', null, 'user') . ' = ?', $dialRuleId);
                        UserModel::getInstance()->deleteUser($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_user'])) {
                            $result->addChild('user', new Model_Result());
                        } else {
                            $_result = UserModel::getInstance()->importUser($data['_user'], $importOpts->getChild('user'));
                            $result->addChild('user', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_user_list']) && (is_array($data['_user_list']) || $data['_user_list'] instanceof Model_Collection_Interface)) {
                    if ($data['_user_list'] instanceof Model_Collection_Interface) {
                        $data['_user_list'] = $data['_user_list']->toArray(true);
                    }

                    if ($dialRuleId && !empty($data['_user_list'])) {
                        foreach ($data['_user_list'] as &$item) {
                            if (is_array($item)) {
                                $item['dial_rule_id'] = $dialRuleId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($dialRuleId && !$importOpts->getChild('user_list')->getAppendLink()) {
                        $opts = UserModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('dial_rule_id', null, 'user') . ' = ?', $dialRuleId);
                        UserModel::getInstance()->deleteUser($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_user_list'])) {
                            $result->addChild('user_list', new Model_Result());
                        } else {
                            $_result = UserModel::getInstance()->importUserList($data['_user_list'], $importOpts->getChild('user_list'));
                            $result->addChild('user_list', $_result);

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

    
    public function importDialRuleList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importDialRule($item, $importOpts);
                $result->addChild('dial_rule', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addDialRule($dialRule)
    {
        $dialRuleId = null;
        $dialRule = new DialRuleEntity($dialRule);
        $dialRuleData = $dialRule->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $dialRuleData = $this->addDialRuleFilter($dialRuleData);

        $validator = $this->addDialRuleValidate($dialRuleData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $dialRuleId = $this->insert($this->getTable(), $dialRuleData);

                if (!$dialRuleId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add DialRule failed', 'add_dialRule_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($dialRuleId))
               ->setValidator($validator);
               
        return $result;
    }

    
    public function getFilterRules()
    {
        if ($this->_filterRules != null) {
            return $this->_filterRules;
        }
        
        $this->_filterRules = array(
            'name' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'max_retries' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'timeout' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
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
            'name' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'name' в $data
                new Zend_Validate_StringLength(0, 255, 'UTF-8'),  // Проверяем строку
            ),
            'max_retries' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Int(),  // Проверяем на integer
            ),
            'timeout' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Int(),  // Проверяем на integer
            ),
            'status' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_InArray(array('active', 'deleted')),  // Проверяем на вхождение
            ),
            'create_date' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Date(array('format' => 'Y-m-d H:i:s')),  // Проверяем дату
            ),

        );

        return $validators;
    }

    
    public function addDialRuleFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialRule
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'name',
                'max_retries',
                'timeout',
                'status',
                'create_date' => date('Y-m-d H:i:s'),

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addDialRuleValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateDialRule($dialRule, Model_Cond $opts = null)
    {
        $dialRule = new DialRuleEntity($dialRule);
        $dialRuleData = $dialRule->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $dialRuleData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update DialRule failed', 'update_dialRule_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleData['id']));
                unset($dialRuleData['id']);
            }
        }

        // Фильтруем данные
        $dialRuleData = $this->updateDialRuleFilter($dialRuleData);

        $validator = $this->updateDialRuleValidate($dialRuleData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $dialRuleData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateDialRuleByDialRule($dialRule, $dialRuleData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $dialRuleIds = $this->getDialRuleIdsFromMixed($dialRule);
        if (!$dialRuleIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update DialRule failed', 'update_dialRule_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleIds));
        
        return $this->updateDialRule($dialRuleData, $opts);
    }

    
    public function updateDialRuleFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialRule
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'name',
                'max_retries',
                'timeout',
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

    
    public function updateDialRuleValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteDialRule(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateDialRule
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteDialRule(Model_Cond $opts = null)
    {
        return $this->_deleteDialRule($opts);
    }

    
    public function deleteDialRuleByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $dialRule и берем первый
        $dialRuleIds = $this->getDialRuleIdsFromMixed($dialRule);

        if (!empty($dialRuleIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleIds));

            $this->_deleteDialRule($opts);
        }
    }

    
    public function truncateDialRule()
    {
        $this->truncate();
    }

    
    
    protected function _getDialRule(Model_Cond $opts = null)
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
                if ($joinEntity == self::JOIN_CALL_FILE) {
                    $join->setRule('call_file',
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('dial_rule_id', null, 'call_file'),
                                    '');
                    continue;
                }

                if ($joinEntity == self::JOIN_DIAL_LOG) {
                    $join->setRule('dial_log',
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('dial_rule_id', null, 'dial_log'),
                                    '');
                    continue;
                }

                if ($joinEntity == self::JOIN_DIAL_RULE_PARAMETR) {
                    $join->setRule('dial_rule_parametr',
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('dial_rule_id', null, 'dial_rule_parametr'),
                                    '');
                    continue;
                }

                if ($joinEntity == self::JOIN_USER) {
                    $join->setRule('user',
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('dial_rule_id', null, 'user'),
                                    '');
                    continue;
                }

            }
        }


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getDialRule(Model_Cond $opts = null)
    {
        return $this->_getDialRule($opts);
    }

    
    public function getDialRuleByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleIds = $this->getDialRuleIdsFromMixed($dialRule);
        if (empty($dialRuleIds) || (count($dialRuleIds) == 1 && reset($dialRuleIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleIds));

        return $this->_getDialRule($opts);
    }

    
    public function getDialRuleList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getDialRule($opts->type(self::FETCH_ALL));
    }

    
    public function getDialRuleListByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByDialRule($dialRule, $opts->type(self::FETCH_ALL));
    }

    
    public function getDialRuleCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getDialRule
        return $this->_getDialRule($opts->type(self::FETCH_COUNT));
    }

    
    public function existsDialRuleByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleIds = $this->getDialRuleIdsFromMixed($dialRule);
        if (empty($dialRuleIds) || (count($dialRuleIds) == 1 && reset($dialRuleIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleIds));

        return $this->_getDialRule($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialRuleByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $callFileList = CallFileModel::getInstance()->getCallFileListByCallFile($callFile);

        $dialRuleIds = array();
        foreach($callFileList as $callFile) {
            $dialRuleIds[] = $callFile->getDialRuleId();
        }

        $dialRuleIds = $this->getDialRuleIdsFromMixed($dialRuleIds);
        if (empty($dialRuleIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleIds));

        return $this->_getDialRule($opts);
    }

    
    public function getDialRuleListByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByCallFile($callFile, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialRuleByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByCallFile($callFile, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialRuleCountByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByCallFile($callFile, $opts->type(self::FETCH_COUNT));
    }

    
    public function getDialRuleByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogList = DialLogModel::getInstance()->getDialLogListByDialLog($dialLog);

        $dialRuleIds = array();
        foreach($dialLogList as $dialLog) {
            $dialRuleIds[] = $dialLog->getDialRuleId();
        }

        $dialRuleIds = $this->getDialRuleIdsFromMixed($dialRuleIds);
        if (empty($dialRuleIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleIds));

        return $this->_getDialRule($opts);
    }

    
    public function getDialRuleListByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByDialLog($dialLog, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialRuleByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByDialLog($dialLog, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialRuleCountByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByDialLog($dialLog, $opts->type(self::FETCH_COUNT));
    }

    
    public function getDialRuleByDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleParametrList = DialRuleParametrModel::getInstance()->getDialRuleParametrListByDialRuleParametr($dialRuleParametr);

        $dialRuleIds = array();
        foreach($dialRuleParametrList as $dialRuleParametr) {
            $dialRuleIds[] = $dialRuleParametr->getDialRuleId();
        }

        $dialRuleIds = $this->getDialRuleIdsFromMixed($dialRuleIds);
        if (empty($dialRuleIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleIds));

        return $this->_getDialRule($opts);
    }

    
    public function getDialRuleListByDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByDialRuleParametr($dialRuleParametr, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialRuleByDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByDialRuleParametr($dialRuleParametr, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialRuleCountByDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByDialRuleParametr($dialRuleParametr, $opts->type(self::FETCH_COUNT));
    }

    
    public function getDialRuleByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $userList = UserModel::getInstance()->getUserListByUser($user);

        $dialRuleIds = array();
        foreach($userList as $user) {
            $dialRuleIds[] = $user->getDialRuleId();
        }

        $dialRuleIds = $this->getDialRuleIdsFromMixed($dialRuleIds);
        if (empty($dialRuleIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleIds));

        return $this->_getDialRule($opts);
    }

    
    public function getDialRuleListByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByUser($user, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialRuleByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByUser($user, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialRuleCountByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByUser($user, $opts->type(self::FETCH_COUNT));
    }

    
    public function getDialRuleByName($name, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $nameIds = $this->_getIdsFromMixed($name, 'strval');

        if (empty($nameIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('name') => $nameIds));

        return $this->getDialRule($opts);
    }

    
    public function existsDialRuleByName($name, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleByName($name, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }


    



    
    
    public function prepareDialRule($data, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_ENTITY, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }
        
        if (!empty($data)) {
            if ($opts->checkWith(self::WITH_CALL_FILE)) {
                $data['_' . self::WITH_CALL_FILE] = CallFileModel::getInstance()->getCallFileByDialRule($data['id'], $opts->getWith(self::WITH_CALL_FILE)->setEntity('call_file'));
            }

            if ($opts->checkWith(self::WITH_CALL_FILE_LIST)) {
                $data['_' . self::WITH_CALL_FILE_LIST] = CallFileModel::getInstance()->getCallFileListByDialRule($data['id'], $opts->getWith(self::WITH_CALL_FILE_LIST)->setEntity('call_file'));
            }

            if ($opts->checkWith(self::WITH_DIAL_LOG)) {
                $data['_' . self::WITH_DIAL_LOG] = DialLogModel::getInstance()->getDialLogByDialRule($data['id'], $opts->getWith(self::WITH_DIAL_LOG)->setEntity('dial_log'));
            }

            if ($opts->checkWith(self::WITH_DIAL_LOG_LIST)) {
                $data['_' . self::WITH_DIAL_LOG_LIST] = DialLogModel::getInstance()->getDialLogListByDialRule($data['id'], $opts->getWith(self::WITH_DIAL_LOG_LIST)->setEntity('dial_log'));
            }

            if ($opts->checkWith(self::WITH_DIAL_RULE_PARAMETR)) {
                $data['_' . self::WITH_DIAL_RULE_PARAMETR] = DialRuleParametrModel::getInstance()->getDialRuleParametrByDialRule($data['id'], $opts->getWith(self::WITH_DIAL_RULE_PARAMETR)->setEntity('dial_rule_parametr'));
            }

            if ($opts->checkWith(self::WITH_DIAL_RULE_PARAMETR_LIST)) {
                $data['_' . self::WITH_DIAL_RULE_PARAMETR_LIST] = DialRuleParametrModel::getInstance()->getDialRuleParametrListByDialRule($data['id'], $opts->getWith(self::WITH_DIAL_RULE_PARAMETR_LIST)->setEntity('dial_rule_parametr'));
            }

            if ($opts->checkWith(self::WITH_USER)) {
                $data['_' . self::WITH_USER] = UserModel::getInstance()->getUserByDialRule($data['id'], $opts->getWith(self::WITH_USER)->setEntity('user'));
            }

            if ($opts->checkWith(self::WITH_USER_LIST)) {
                $data['_' . self::WITH_USER_LIST] = UserModel::getInstance()->getUserListByDialRule($data['id'], $opts->getWith(self::WITH_USER_LIST)->setEntity('user'));
            }

     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new DialRuleEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareDialRuleList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareDialRule($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new DialRuleCollection($data);
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

    
    
    public function getDialRuleIdsFromMixed($dialRule)
    {
        if (is_object($dialRule)
            && !$dialRule instanceof DialRuleEntity
            && !$dialRule instanceof DialRuleCollection
            && !$dialRule instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($dialRule);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
