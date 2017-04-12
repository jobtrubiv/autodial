<?php


abstract class DialSettingsModelAbstract extends Model_Db_Mysql_Abstract
{
    protected $_filterRules;

    protected function _setupTableName()
    {
        $this->_table = 'dial_settings';
    }

    
    
    public function importDialSettings($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $dialSettingsId = null;

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
            if (!$dialSettingsId && array_key_exists('id', $data)) {
                $dialSettingsId = DialSettingsModel::getInstance()->existsDialSettingsByDialSettings($data['id']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (!$dialSettingsId) {
                try {
                    $_result = $this->addDialSettings($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $dialSettingsId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import DialSettings failed: ' . $ex->getMessage(), 'import_dial_settings_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateDialSettings($data, DialSettingsModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialSettingsId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateDialSettings($_data, DialSettingsModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $dialSettingsId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($dialSettingsId));

            if (!$dialSettingsId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($dialSettingsId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {


            }

        }

        return $result;
    }

    
    public function importDialSettingsList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importDialSettings($item, $importOpts);
                $result->addChild('dial_settings', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addDialSettings($dialSettings)
    {
        $dialSettingsId = null;
        $dialSettings = new DialSettingsEntity($dialSettings);
        $dialSettingsData = $dialSettings->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $dialSettingsData = $this->addDialSettingsFilter($dialSettingsData);

        $validator = $this->addDialSettingsValidate($dialSettingsData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $dialSettingsId = $this->insert($this->getTable(), $dialSettingsData);

                if (!$dialSettingsId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add DialSettings failed', 'add_dialSettings_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($dialSettingsId))
               ->setValidator($validator);
               
        return $result;
    }

    
    public function getFilterRules()
    {
        if ($this->_filterRules != null) {
            return $this->_filterRules;
        }
        
        $this->_filterRules = array(
            'trunk' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'caller_id' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'context' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'retry_time' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'wait_time' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'active' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),

        );

        return $this->_filterRules;
    }

    
    protected function getValidatorRules($optionalPresence = false)
    {
        $presence = $optionalPresence ? Zend_Filter_Input::PRESENCE_OPTIONAL : null;

        $validators = array(
            'trunk' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 50, 'UTF-8'),  // Проверяем строку
            ),
            'caller_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'caller_id' в $data
                new Zend_Validate_StringLength(0, 50, 'UTF-8'),  // Проверяем строку
            ),
            'context' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'context' в $data
                new Zend_Validate_StringLength(0, 50, 'UTF-8'),  // Проверяем строку
            ),
            'retry_time' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Int(),  // Проверяем на integer
            ),
            'wait_time' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Int(),  // Проверяем на integer
            ),
            'active' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_InArray(array('y', 'n')),  // Проверяем на вхождение
            ),

        );

        return $validators;
    }

    
    public function addDialSettingsFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialSettings
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'trunk',
                'caller_id',
                'context',
                'retry_time',
                'wait_time',
                'active',

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addDialSettingsValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateDialSettings($dialSettings, Model_Cond $opts = null)
    {
        $dialSettings = new DialSettingsEntity($dialSettings);
        $dialSettingsData = $dialSettings->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $dialSettingsData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update DialSettings failed', 'update_dialSettings_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $dialSettingsData['id']));
                unset($dialSettingsData['id']);
            }
        }

        // Фильтруем данные
        $dialSettingsData = $this->updateDialSettingsFilter($dialSettingsData);

        $validator = $this->updateDialSettingsValidate($dialSettingsData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $dialSettingsData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateDialSettingsByDialSettings($dialSettings, $dialSettingsData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $dialSettingsIds = $this->getDialSettingsIdsFromMixed($dialSettings);
        if (!$dialSettingsIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update DialSettings failed', 'update_dialSettings_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialSettingsIds));
        
        return $this->updateDialSettings($dialSettingsData, $opts);
    }

    
    public function updateDialSettingsFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $dialSettings
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'trunk',
                'caller_id',
                'context',
                'retry_time',
                'wait_time',
                'active',

        );

        $_data = $this->getDataValues($data, $defaults);


        if (empty($_data)) {
            return array();
        }
        
        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();

        return $_data;
    }

    
    public function updateDialSettingsValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteDialSettings(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateDialSettings
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteDialSettings(Model_Cond $opts = null)
    {
        return $this->_deleteDialSettings($opts);
    }

    
    public function deleteDialSettingsByDialSettings($dialSettings, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $dialSettings и берем первый
        $dialSettingsIds = $this->getDialSettingsIdsFromMixed($dialSettings);

        if (!empty($dialSettingsIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $dialSettingsIds));

            $this->_deleteDialSettings($opts);
        }
    }

    
    public function truncateDialSettings()
    {
        $this->truncate();
    }

    
    
    protected function _getDialSettings(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $opts->from($this->getTable());


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getDialSettings(Model_Cond $opts = null)
    {
        return $this->_getDialSettings($opts);
    }

    
    public function getDialSettingsByDialSettings($dialSettings, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialSettingsIds = $this->getDialSettingsIdsFromMixed($dialSettings);
        if (empty($dialSettingsIds) || (count($dialSettingsIds) == 1 && reset($dialSettingsIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialSettingsIds));

        return $this->_getDialSettings($opts);
    }

    
    public function getDialSettingsList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getDialSettings($opts->type(self::FETCH_ALL));
    }

    
    public function getDialSettingsListByDialSettings($dialSettings, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getDialSettingsByDialSettings($dialSettings, $opts->type(self::FETCH_ALL));
    }

    
    public function getDialSettingsCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getDialSettings
        return $this->_getDialSettings($opts->type(self::FETCH_COUNT));
    }

    
    public function existsDialSettingsByDialSettings($dialSettings, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialSettingsIds = $this->getDialSettingsIdsFromMixed($dialSettings);
        if (empty($dialSettingsIds) || (count($dialSettingsIds) == 1 && reset($dialSettingsIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $dialSettingsIds));

        return $this->_getDialSettings($opts->columns(array('id'))->type(self::FETCH_ONE));
    }


    



    
    
    public function prepareDialSettings($data, Model_Cond $opts = null)
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
                return new DialSettingsEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareDialSettingsList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareDialSettings($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new DialSettingsCollection($data);
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

    
    
    public function getDialSettingsIdsFromMixed($dialSettings)
    {
        if (is_object($dialSettings)
            && !$dialSettings instanceof DialSettingsEntity
            && !$dialSettings instanceof DialSettingsCollection
            && !$dialSettings instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($dialSettings);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
