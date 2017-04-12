<?php


abstract class DialRuleParametrModelAbstract extends Model_Db_Mysql_Abstract
{
    
    const WITH_DIAL_RULE = 'dial_rule';
    const JOIN_DIAL_RULE = 'dial_rule';
    protected $_filterRules;

    protected function _setupTableName()
    {
        $this->_table = 'dial_rule_parametr';
    }

    
    
    public function importDialRuleParametr($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $dialRuleParametrId = null;

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
            if (!$dialRuleParametrId && array_key_exists('id', $data)) {
                $dialRuleParametrId = DialRuleParametrModel::getInstance()->existsDialRuleParametrByDialRuleParametr($data['id']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (array_key_exists('dial_rule_id', $data)) {
                $_data['dial_rule_id'] = $data['dial_rule_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$dialRuleParametrId && !array_key_exists('dial_rule_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_dial_rule', $data) && !empty($data['_dial_rule'])) {
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

            if (!$dialRuleParametrId) {
                try {
                    $_result = $this->addDialRuleParametr($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $dialRuleParametrId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import DialRuleParametr failed: ' . $ex->getMessage(), 'import_dial_rule_parametr_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateDialRuleParametr($data, DialRuleParametrModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialRuleParametrId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateDialRuleParametr($_data, DialRuleParametrModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialRuleParametrId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($dialRuleParametrId));

            if (!$dialRuleParametrId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($dialRuleParametrId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {


            }

        }

        return $result;
    }

    
    public function importDialRuleParametrList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importDialRuleParametr($item, $importOpts);
                $result->addChild('dial_rule_parametr', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addDialRuleParametr($dialRuleParametr)
    {
        $dialRuleParametrId = null;
        $dialRuleParametr = new DialRuleParametrEntity($dialRuleParametr);
        $dialRuleParametrData = $dialRuleParametr->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $dialRuleParametrData = $this->addDialRuleParametrFilter($dialRuleParametrData);

        $validator = $this->addDialRuleParametrValidate($dialRuleParametrData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $dialRuleParametrId = $this->insert($this->getTable(), $dialRuleParametrData);

                if (!$dialRuleParametrId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add DialRuleParametr failed', 'add_dialRuleParametr_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($dialRuleParametrId))
               ->setValidator($validator);
               
        return $result;
    }

    
    public function getFilterRules()
    {
        if ($this->_filterRules != null) {
            return $this->_filterRules;
        }
        
        $this->_filterRules = array(
            'dial_rule_id' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'priority' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'action' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'action_data' => array(
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
            'dial_rule_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'dial_rule_id' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
                new Zend_Validate_Db_RecordExists(array('adapter' => $this->getDb(), 'table' => 'dial_rule', 'field' => 'id')),  // Существование связи
            ),
            'priority' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'priority' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
            ),
            'action' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_InArray(array('play_file', 'speech', 'digit')),  // Проверяем на вхождение
            ),
            'action_data' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'action_data' в $data
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

    
    public function addDialRuleParametrFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialRuleParametr
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'dial_rule_id',
                'priority',
                'action',
                'action_data',
                'create_date' => date('Y-m-d H:i:s'),

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addDialRuleParametrValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        $dialRuleParametr = new DialRuleParametrEntity($dialRuleParametr);
        $dialRuleParametrData = $dialRuleParametr->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $dialRuleParametrData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update DialRuleParametr failed', 'update_dialRuleParametr_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleParametrData['id']));
                unset($dialRuleParametrData['id']);
            }
        }

        // Фильтруем данные
        $dialRuleParametrData = $this->updateDialRuleParametrFilter($dialRuleParametrData);

        $validator = $this->updateDialRuleParametrValidate($dialRuleParametrData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $dialRuleParametrData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateDialRuleParametrByDialRuleParametr($dialRuleParametr, $dialRuleParametrData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $dialRuleParametrIds = $this->getDialRuleParametrIdsFromMixed($dialRuleParametr);
        if (!$dialRuleParametrIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update DialRuleParametr failed', 'update_dialRuleParametr_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleParametrIds));
        
        return $this->updateDialRuleParametr($dialRuleParametrData, $opts);
    }

    
    public function updateDialRuleParametrFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialRuleParametr
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'dial_rule_id',
                'priority',
                'action',
                'action_data',
                'create_date',

        );

        $_data = $this->getDataValues($data, $defaults);


        if (empty($_data)) {
            return array();
        }
        
        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();

        return $_data;
    }

    
    public function updateDialRuleParametrValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteDialRuleParametr(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateDialRuleParametr
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteDialRuleParametr(Model_Cond $opts = null)
    {
        return $this->_deleteDialRuleParametr($opts);
    }

    
    public function deleteDialRuleParametrByDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $dialRuleParametr и берем первый
        $dialRuleParametrIds = $this->getDialRuleParametrIdsFromMixed($dialRuleParametr);

        if (!empty($dialRuleParametrIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleParametrIds));

            $this->_deleteDialRuleParametr($opts);
        }
    }

    
    public function truncateDialRuleParametr()
    {
        $this->truncate();
    }

    
    
    protected function _getDialRuleParametr(Model_Cond $opts = null)
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
                if ($joinEntity == self::JOIN_DIAL_RULE) {
                    $join->setRule('dial_rule',
                                    $this->getTableWithColumnQuoted('dial_rule_id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('id', null, 'dial_rule'),
                                    '');
                    continue;
                }

            }
        }


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getDialRuleParametr(Model_Cond $opts = null)
    {
        return $this->_getDialRuleParametr($opts);
    }

    
    public function getDialRuleParametrByDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleParametrIds = $this->getDialRuleParametrIdsFromMixed($dialRuleParametr);
        if (empty($dialRuleParametrIds) || (count($dialRuleParametrIds) == 1 && reset($dialRuleParametrIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleParametrIds));

        return $this->_getDialRuleParametr($opts);
    }

    
    public function getDialRuleParametrList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getDialRuleParametr($opts->type(self::FETCH_ALL));
    }

    
    public function getDialRuleParametrListByDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleParametrByDialRuleParametr($dialRuleParametr, $opts->type(self::FETCH_ALL));
    }

    
    public function getDialRuleParametrCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getDialRuleParametr
        return $this->_getDialRuleParametr($opts->type(self::FETCH_COUNT));
    }

    
    public function existsDialRuleParametrByDialRuleParametr($dialRuleParametr, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleParametrIds = $this->getDialRuleParametrIdsFromMixed($dialRuleParametr);
        if (empty($dialRuleParametrIds) || (count($dialRuleParametrIds) == 1 && reset($dialRuleParametrIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialRuleParametrIds));

        return $this->_getDialRuleParametr($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialRuleParametrByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleIds = DialRuleModel::getInstance()->getDialRuleIdsFromMixed($dialRule);
        if (empty($dialRuleIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('dial_rule_id') => $dialRuleIds));

        return $this->_getDialRuleParametr($opts);
    }

    
    public function getDialRuleParametrListByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleParametrByDialRule($dialRule, $opts->type(self::FETCH_ALL));
    }

    
    public function existsDialRuleParametrByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleParametrByDialRule($dialRule, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getDialRuleParametrCountByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getDialRuleParametrByDialRule($dialRule, $opts->type(self::FETCH_COUNT));
    }


    



    
    
    public function prepareDialRuleParametr($data, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_ENTITY, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }
        
        if (!empty($data)) {
            if ($opts->checkWith(self::WITH_DIAL_RULE)) {
                $data['_' . self::WITH_DIAL_RULE] = DialRuleModel::getInstance()->getDialRuleByDialRule($data['dial_rule_id'], $opts->getWith(self::WITH_DIAL_RULE)->setEntity('dial_rule'));
            }

     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new DialRuleParametrEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareDialRuleParametrList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareDialRuleParametr($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new DialRuleParametrCollection($data);
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

    
    
    public function getDialRuleParametrIdsFromMixed($dialRuleParametr)
    {
        if (is_object($dialRuleParametr)
            && !$dialRuleParametr instanceof DialRuleParametrEntity
            && !$dialRuleParametr instanceof DialRuleParametrCollection
            && !$dialRuleParametr instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($dialRuleParametr);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
