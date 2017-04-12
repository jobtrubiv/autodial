<?php


abstract class CallFileModelAbstract extends Model_Db_Mysql_Abstract
{
    const WITH_DIAL_RULE = 'dial_rule';
    const WITH_ADMIN = 'admin';
    const JOIN_DIAL_RULE = 'dial_rule';
    const JOIN_ADMIN = 'admin';
    protected $_filterRules;

    protected function _setupTableName()
    {
        $this->_table = 'call_file';
    }

    
    
    public function importCallFile($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $callFileId = null;

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
            if (!$callFileId && array_key_exists('id', $data)) {
                $callFileId = CallFileModel::getInstance()->existsCallFileByCallFile($data['id']);
            }

            if (!$callFileId && array_key_exists('dial_rule_id', $data) && array_key_exists('hash', $data)) {
                $callFileId = CallFileModel::getInstance()->existsCallFileByDialRuleAndHash($data['dial_rule_id'], $data['hash']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (array_key_exists('dial_rule_id', $data)) {
                $_data['dial_rule_id'] = $data['dial_rule_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$callFileId && !array_key_exists('dial_rule_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_dial_rule', $data) && !empty($data['_dial_rule'])) {
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

                    if (array_key_exists('dial_rule_id', $data) && array_key_exists('hash', $data)) {
                        $callFileId = CallFileModel::getInstance()->existsCallFileByDialRuleAndHash($data['dial_rule_id'], $data['hash']);
                    }
                }
            }

            if (array_key_exists('admin_id', $data)) {
                $_data['admin_id'] = $data['admin_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$callFileId && !array_key_exists('admin_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_admin', $data) && !empty($data['_admin'])) {
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

            if (!$callFileId) {
                try {
                    $_result = $this->addCallFile($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $callFileId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import CallFile failed: ' . $ex->getMessage(), 'import_call_file_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateCallFile($data, CallFileModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $callFileId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateCallFile($_data, CallFileModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $callFileId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($callFileId));

            if (!$callFileId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($callFileId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {


            }

        }

        return $result;
    }

    
    public function importCallFileList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importCallFile($item, $importOpts);
                $result->addChild('call_file', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addCallFile($callFile)
    {
        $callFileId = null;
        $callFile = new CallFileEntity($callFile);
        $callFileData = $callFile->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $callFileData = $this->addCallFileFilter($callFileData);

        $validator = $this->addCallFileValidate($callFileData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $callFileId = $this->insert($this->getTable(), $callFileData);

                if (!$callFileId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add CallFile failed', 'add_callFile_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($callFileId))
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
            'admin_id' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'name' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'hash' => array(
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
            'admin_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'admin_id' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
                new Zend_Validate_Db_RecordExists(array('adapter' => $this->getDb(), 'table' => 'admin', 'field' => 'id')),  // Существование связи
            ),
            'name' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'name' в $data
                new Zend_Validate_StringLength(0, 255, 'UTF-8'),  // Проверяем строку
            ),
            'hash' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'hash' в $data
                new Zend_Validate_StringLength(0, 255, 'UTF-8'),  // Проверяем строку
            ),
            'create_date' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Date(array('format' => 'Y-m-d H:i:s')),  // Проверяем дату
            ),

        );

        return $validators;
    }

    
    public function addCallFileFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $callFile
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'dial_rule_id',
                'admin_id',
                'name',
                'hash',
                'create_date' => date('Y-m-d H:i:s'),

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addCallFileValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateCallFile($callFile, Model_Cond $opts = null)
    {
        $callFile = new CallFileEntity($callFile);
        $callFileData = $callFile->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $callFileData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update CallFile failed', 'update_callFile_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $callFileData['id']));
                unset($callFileData['id']);
            }
        }

        // Фильтруем данные
        $callFileData = $this->updateCallFileFilter($callFileData);

        $validator = $this->updateCallFileValidate($callFileData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $callFileData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateCallFileByCallFile($callFile, $callFileData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $callFileIds = $this->getCallFileIdsFromMixed($callFile);
        if (!$callFileIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update CallFile failed', 'update_callFile_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $callFileIds));
        
        return $this->updateCallFile($callFileData, $opts);
    }

    
    public function updateCallFileFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $callFile
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'dial_rule_id',
                'admin_id',
                'name',
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

    
    public function updateCallFileValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteCallFile(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateCallFile
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteCallFile(Model_Cond $opts = null)
    {
        return $this->_deleteCallFile($opts);
    }

    
    public function deleteCallFileByCallFile($callFile, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $callFile и берем первый
        $callFileIds = $this->getCallFileIdsFromMixed($callFile);

        if (!empty($callFileIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $callFileIds));

            $this->_deleteCallFile($opts);
        }
    }

    
    public function truncateCallFile()
    {
        $this->truncate();
    }

    
    
    protected function _getCallFile(Model_Cond $opts = null)
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

                if ($joinEntity == self::JOIN_ADMIN) {
                    $join->setRule('admin',
                                    $this->getTableWithColumnQuoted('admin_id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('id', null, 'admin'),
                                    '');
                    continue;
                }

            }
        }


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getCallFile(Model_Cond $opts = null)
    {
        return $this->_getCallFile($opts);
    }

    
    public function getCallFileByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $callFileIds = $this->getCallFileIdsFromMixed($callFile);
        if (empty($callFileIds) || (count($callFileIds) == 1 && reset($callFileIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $callFileIds));

        return $this->_getCallFile($opts);
    }

    
    public function getCallFileList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getCallFile($opts->type(self::FETCH_ALL));
    }

    
    public function getCallFileListByCallFile($callFile, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getCallFileByCallFile($callFile, $opts->type(self::FETCH_ALL));
    }

    
    public function getCallFileCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getCallFile
        return $this->_getCallFile($opts->type(self::FETCH_COUNT));
    }

    
    public function existsCallFileByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $callFileIds = $this->getCallFileIdsFromMixed($callFile);
        if (empty($callFileIds) || (count($callFileIds) == 1 && reset($callFileIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $callFileIds));

        return $this->_getCallFile($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getCallFileByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleIds = DialRuleModel::getInstance()->getDialRuleIdsFromMixed($dialRule);
        if (empty($dialRuleIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('dial_rule_id') => $dialRuleIds));

        return $this->_getCallFile($opts);
    }

    
    public function getCallFileListByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getCallFileByDialRule($dialRule, $opts->type(self::FETCH_ALL));
    }

    
    public function existsCallFileByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getCallFileByDialRule($dialRule, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getCallFileCountByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getCallFileByDialRule($dialRule, $opts->type(self::FETCH_COUNT));
    }

    
    public function getCallFileByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $adminIds = AdminModel::getInstance()->getAdminIdsFromMixed($admin);
        if (empty($adminIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('admin_id') => $adminIds));

        return $this->_getCallFile($opts);
    }

    
    public function getCallFileListByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getCallFileByAdmin($admin, $opts->type(self::FETCH_ALL));
    }

    
    public function existsCallFileByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getCallFileByAdmin($admin, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getCallFileCountByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getCallFileByAdmin($admin, $opts->type(self::FETCH_COUNT));
    }

    
    public function getCallFileByDialRuleAndHash($dialRule, $hash, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleIds = DialRuleModel::getInstance()->getDialRuleIdsFromMixed($dialRule);
        $hashIds = $this->_getIdsFromMixed($hash, 'strval');

        if (empty($dialRuleIds) || empty($hashIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('dial_rule_id') => $dialRuleIds))
             ->where(array($this->getTableWithColumnQuoted('hash') => $hashIds));

        return $this->getCallFile($opts);
    }

    
    public function existsCallFileByDialRuleAndHash($dialRule, $hash, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getCallFileByDialRuleAndHash($dialRule, $hash, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }


    



    
    
    public function prepareCallFile($data, Model_Cond $opts = null)
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

            if ($opts->checkWith(self::WITH_ADMIN)) {
                $data['_' . self::WITH_ADMIN] = AdminModel::getInstance()->getAdminByAdmin($data['admin_id'], $opts->getWith(self::WITH_ADMIN)->setEntity('admin'));
            }

     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new CallFileEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareCallFileList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareCallFile($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new CallFileCollection($data);
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

    
    
    public function getCallFileIdsFromMixed($callFile)
    {
        if (is_object($callFile)
            && !$callFile instanceof CallFileEntity
            && !$callFile instanceof CallFileCollection
            && !$callFile instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($callFile);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
