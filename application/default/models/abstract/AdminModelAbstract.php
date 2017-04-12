<?php


abstract class AdminModelAbstract extends Model_Db_Mysql_Abstract
{
    const WITH_CALL_FILE = 'call_file';
    const WITH_CALL_FILE_LIST = 'call_file_list';
    const WITH_DIAL_LOG = 'dial_log';
    const WITH_DIAL_LOG_LIST = 'dial_log_list';
    const JOIN_CALL_FILE = 'call_file';
    const JOIN_DIAL_LOG = 'dial_log';

    protected $_filterRules;
    
    protected function _setupTableName()
    {
        $this->_table = 'admin';
    }

    public function importAdmin($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $adminId = null;

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
            if (!$adminId && array_key_exists('id', $data)) {
                $adminId = AdminModel::getInstance()->existsAdminByAdmin($data['id']);
            }

            if (!$adminId && array_key_exists('login', $data)) {
                $adminId = AdminModel::getInstance()->existsAdminByLogin($data['login']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (!$adminId) {
                try {
                    $_result = $this->addAdmin($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $adminId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import Admin failed: ' . $ex->getMessage(), 'import_admin_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateAdmin($data, AdminModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $adminId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateAdmin($_data, AdminModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $adminId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($adminId));

            if (!$adminId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($adminId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {

                if (isset($data['_call_file']) && (is_array($data['_call_file']) || $data['_call_file'] instanceof Model_Entity_Interface)) {
                    if ($data['_call_file'] instanceof Model_Entity_Interface) {
                        $data['_call_file'] = $data['_call_file']->toArray(true);
                    }

                    if ($adminId) {
                        $data['_call_file']['admin_id'] = $adminId;
                    }

                    $_result = new Model_Result();
                    if ($adminId && !$importOpts->getChild('call_file')->getAppendLink()) {
                        $opts = CallFileModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('admin_id', null, 'call_file') . ' = ?', $adminId);
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

                    if ($adminId && !empty($data['_call_file_list'])) {
                        foreach ($data['_call_file_list'] as &$item) {
                            if (is_array($item)) {
                                $item['admin_id'] = $adminId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($adminId && !$importOpts->getChild('call_file_list')->getAppendLink()) {
                        $opts = CallFileModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('admin_id', null, 'call_file') . ' = ?', $adminId);
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

                    if ($adminId) {
                        $data['_dial_log']['admin_id'] = $adminId;
                    }

                    $_result = new Model_Result();
                    if ($adminId && !$importOpts->getChild('dial_log')->getAppendLink()) {
                        $opts = DialLogModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('admin_id', null, 'dial_log') . ' = ?', $adminId);
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

                    if ($adminId && !empty($data['_dial_log_list'])) {
                        foreach ($data['_dial_log_list'] as &$item) {
                            if (is_array($item)) {
                                $item['admin_id'] = $adminId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($adminId && !$importOpts->getChild('dial_log_list')->getAppendLink()) {
                        $opts = DialLogModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('admin_id', null, 'dial_log') . ' = ?', $adminId);
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


            }

        }

        return $result;
    }

    public function importAdminList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importAdmin($item, $importOpts);
                $result->addChild('admin', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }

    public function addAdmin($admin)
    {
        $adminId = null;
        $admin = new AdminEntity($admin);
        $adminData = $admin->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $adminData = $this->addAdminFilter($adminData);

        $validator = $this->addAdminValidate($adminData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $adminId = $this->insert($this->getTable(), $adminData);

                if (!$adminId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add Admin failed', 'add_admin_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($adminId))
               ->setValidator($validator);
               
        return $result;
    }

    public function getFilterRules()
    {
        if ($this->_filterRules != null) {
            return $this->_filterRules;
        }
        
        $this->_filterRules = array(
            'type' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'login' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'password' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'email' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'surname' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'name' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'patronymic' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
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
            'type' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_InArray(array('administrator', 'operator')),  // Проверяем на вхождение
            ),
            'login' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'login' в $data
                new Zend_Validate_StringLength(0, 128, 'UTF-8'),  // Проверяем строку
            ),
            'password' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'password' в $data
                new Zend_Validate_StringLength(0, 50, 'UTF-8'),  // Проверяем строку
            ),
            'email' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 128, 'UTF-8'),  // Проверяем строку
            ),
            'surname' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'surname' в $data
                new Zend_Validate_StringLength(0, 128, 'UTF-8'),  // Проверяем строку
            ),
            'name' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'name' в $data
                new Zend_Validate_StringLength(0, 128, 'UTF-8'),  // Проверяем строку
            ),
            'patronymic' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 128, 'UTF-8'),  // Проверяем строку
            ),
            'status' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_InArray(array('active', 'blocked', 'deleted')),  // Проверяем на вхождение
            ),
            'create_date' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_Date(array('format' => 'Y-m-d H:i:s')),  // Проверяем дату
            ),

        );

        return $validators;
    }

    public function addAdminFilter($data)
    {
        $defaults = array(
                'type',
                'login',
                'password',
                'email',
                'surname',
                'name',
                'patronymic',
                'status',
                'create_date' => date('Y-m-d H:i:s'),

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    public function addAdminValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    public function updateAdmin($admin, Model_Cond $opts = null)
    {
        $admin = new AdminEntity($admin);
        $adminData = $admin->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $adminData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update Admin failed', 'update_admin_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $adminData['id']));
                unset($adminData['id']);
            }
        }

        // Фильтруем данные
        $adminData = $this->updateAdminFilter($adminData);

        $validator = $this->updateAdminValidate($adminData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $adminData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateAdminByAdmin($admin, $adminData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $adminIds = $this->getAdminIdsFromMixed($admin);
        if (!$adminIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update Admin failed', 'update_admin_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $adminIds));
        
        return $this->updateAdmin($adminData, $opts);
    }

    
    public function updateAdminFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $admin
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'type',
                'login',
                'password',
                'email',
                'surname',
                'name',
                'patronymic',
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

    
    public function updateAdminValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteAdmin(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateAdmin
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteAdmin(Model_Cond $opts = null)
    {
        return $this->_deleteAdmin($opts);
    }

    
    public function deleteAdminByAdmin($admin, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $admin и берем первый
        $adminIds = $this->getAdminIdsFromMixed($admin);

        if (!empty($adminIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $adminIds));

            $this->_deleteAdmin($opts);
        }
    }

    
    public function truncateAdmin()
    {
        $this->truncate();
    }

    
    
    protected function _getAdmin(Model_Cond $opts = null)
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
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('admin_id', null, 'call_file'),
                                    '');
                    continue;
                }

                if ($joinEntity == self::JOIN_DIAL_LOG) {
                    $join->setRule('dial_log',
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('admin_id', null, 'dial_log'),
                                    '');
                    continue;
                }

            }
        }


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getAdmin(Model_Cond $opts = null)
    {
        return $this->_getAdmin($opts);
    }

    
    public function getAdminByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $adminIds = $this->getAdminIdsFromMixed($admin);
        if (empty($adminIds) || (count($adminIds) == 1 && reset($adminIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $adminIds));

        return $this->_getAdmin($opts);
    }

    
    public function getAdminList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getAdmin($opts->type(self::FETCH_ALL));
    }

    
    public function getAdminListByAdmin($admin, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getAdminByAdmin($admin, $opts->type(self::FETCH_ALL));
    }

    
    public function getAdminCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getAdmin
        return $this->_getAdmin($opts->type(self::FETCH_COUNT));
    }

    
    public function existsAdminByAdmin($admin, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $adminIds = $this->getAdminIdsFromMixed($admin);
        if (empty($adminIds) || (count($adminIds) == 1 && reset($adminIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $adminIds));

        return $this->_getAdmin($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getAdminByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $callFileList = CallFileModel::getInstance()->getCallFileListByCallFile($callFile);

        $adminIds = array();
        foreach($callFileList as $callFile) {
            $adminIds[] = $callFile->getAdminId();
        }

        $adminIds = $this->getAdminIdsFromMixed($adminIds);
        if (empty($adminIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $adminIds));

        return $this->_getAdmin($opts);
    }

    
    public function getAdminListByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getAdminByCallFile($callFile, $opts->type(self::FETCH_ALL));
    }

    
    public function existsAdminByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getAdminByCallFile($callFile, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getAdminCountByCallFile($callFile, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getAdminByCallFile($callFile, $opts->type(self::FETCH_COUNT));
    }

    
    public function getAdminByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogList = DialLogModel::getInstance()->getDialLogListByDialLog($dialLog);

        $adminIds = array();
        foreach($dialLogList as $dialLog) {
            $adminIds[] = $dialLog->getAdminId();
        }

        $adminIds = $this->getAdminIdsFromMixed($adminIds);
        if (empty($adminIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $adminIds));

        return $this->_getAdmin($opts);
    }

    
    public function getAdminListByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getAdminByDialLog($dialLog, $opts->type(self::FETCH_ALL));
    }

    
    public function existsAdminByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getAdminByDialLog($dialLog, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getAdminCountByDialLog($dialLog, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getAdminByDialLog($dialLog, $opts->type(self::FETCH_COUNT));
    }

    
    public function getAdminByLogin($login, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $loginIds = $this->_getIdsFromMixed($login, 'strval');

        if (empty($loginIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('login') => $loginIds));

        return $this->getAdmin($opts);
    }

    
    public function existsAdminByLogin($login, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getAdminByLogin($login, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }


    



    
    
    public function prepareAdmin($data, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_ENTITY, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }
        
        if (!empty($data)) {
            if ($opts->checkWith(self::WITH_CALL_FILE)) {
                $data['_' . self::WITH_CALL_FILE] = CallFileModel::getInstance()->getCallFileByAdmin($data['id'], $opts->getWith(self::WITH_CALL_FILE)->setEntity('call_file'));
            }

            if ($opts->checkWith(self::WITH_CALL_FILE_LIST)) {
                $data['_' . self::WITH_CALL_FILE_LIST] = CallFileModel::getInstance()->getCallFileListByAdmin($data['id'], $opts->getWith(self::WITH_CALL_FILE_LIST)->setEntity('call_file'));
            }

            if ($opts->checkWith(self::WITH_DIAL_LOG)) {
                $data['_' . self::WITH_DIAL_LOG] = DialLogModel::getInstance()->getDialLogByAdmin($data['id'], $opts->getWith(self::WITH_DIAL_LOG)->setEntity('dial_log'));
            }

            if ($opts->checkWith(self::WITH_DIAL_LOG_LIST)) {
                $data['_' . self::WITH_DIAL_LOG_LIST] = DialLogModel::getInstance()->getDialLogListByAdmin($data['id'], $opts->getWith(self::WITH_DIAL_LOG_LIST)->setEntity('dial_log'));
            }

     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new AdminEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareAdminList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareAdmin($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new AdminCollection($data);
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

    
    
    public function getAdminIdsFromMixed($admin)
    {
        if (is_object($admin)
            && !$admin instanceof AdminEntity
            && !$admin instanceof AdminCollection
            && !$admin instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($admin);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
