<?php


abstract class UserModelAbstract extends Model_Db_Mysql_Abstract
{
    const WITH_DIAL_RULE = 'dial_rule';
    const WITH_DIAL_LOG_CALL = 'dial_log_call';
    const WITH_DIAL_LOG_CALL_LIST = 'dial_log_call_list';
    const WITH_IDENTIFICTOR = 'identifictor';
    const WITH_IDENTIFICTOR_LIST = 'identifictor_list';
    const JOIN_DIAL_RULE = 'dial_rule';
    const JOIN_DIAL_LOG_CALL = 'dial_log_call';
    const JOIN_IDENTIFICTOR = 'identifictor';

    protected $_filterRules;    
    
    protected function _setupTableName()
    {
        $this->_table = 'user';
    }

    
    
    public function importUser($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $userId = null;

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
            if (!$userId && array_key_exists('id', $data)) {
                $userId = UserModel::getInstance()->existsUserByUser($data['id']);
            }

            if (!$userId && array_key_exists('dial_rule_id', $data) && array_key_exists('phone', $data)) {
                $userId = UserModel::getInstance()->existsUserByDialRuleAndPhone($data['dial_rule_id'], $data['phone']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (array_key_exists('dial_rule_id', $data)) {
                $_data['dial_rule_id'] = $data['dial_rule_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$userId && !array_key_exists('dial_rule_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_dial_rule', $data) && !empty($data['_dial_rule'])) {
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

                    if (array_key_exists('dial_rule_id', $data) && array_key_exists('phone', $data)) {
                        $userId = UserModel::getInstance()->existsUserByDialRuleAndPhone($data['dial_rule_id'], $data['phone']);
                    }
                }
            }

            if (!$userId) {
                try {
                    $_result = $this->addUser($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $userId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import User failed: ' . $ex->getMessage(), 'import_user_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateUser($data, UserModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $userId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateUser($_data, UserModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $userId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($userId));

            if (!$userId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($userId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {

                if (isset($data['_dial_log_call']) && (is_array($data['_dial_log_call']) || $data['_dial_log_call'] instanceof Model_Entity_Interface)) {
                    if ($data['_dial_log_call'] instanceof Model_Entity_Interface) {
                        $data['_dial_log_call'] = $data['_dial_log_call']->toArray(true);
                    }

                    if ($userId) {
                        $data['_dial_log_call']['user_id'] = $userId;
                    }

                    $_result = new Model_Result();
                    if ($userId && !$importOpts->getChild('dial_log_call')->getAppendLink()) {
                        $opts = DialLogCallModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('user_id', null, 'dial_log_call') . ' = ?', $userId);
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

                    if ($userId && !empty($data['_dial_log_call_list'])) {
                        foreach ($data['_dial_log_call_list'] as &$item) {
                            if (is_array($item)) {
                                $item['user_id'] = $userId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($userId && !$importOpts->getChild('dial_log_call_list')->getAppendLink()) {
                        $opts = DialLogCallModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('user_id', null, 'dial_log_call') . ' = ?', $userId);
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

                if (isset($data['_identifictor']) && (is_array($data['_identifictor']) || $data['_identifictor'] instanceof Model_Entity_Interface)) {
                    if ($data['_identifictor'] instanceof Model_Entity_Interface) {
                        $data['_identifictor'] = $data['_identifictor']->toArray(true);
                    }

                    if ($userId) {
                        $data['_identifictor']['user_id'] = $userId;
                    }

                    $_result = new Model_Result();
                    if ($userId && !$importOpts->getChild('identifictor')->getAppendLink()) {
                        $opts = IdentifictorModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('user_id', null, 'identifictor') . ' = ?', $userId);
                        IdentifictorModel::getInstance()->deleteIdentifictor($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_identifictor'])) {
                            $result->addChild('identifictor', new Model_Result());
                        } else {
                            $_result = IdentifictorModel::getInstance()->importIdentifictor($data['_identifictor'], $importOpts->getChild('identifictor'));
                            $result->addChild('identifictor', $_result);

                            if (!$importOpts->getIgnoreErrors() && $_result->isError()) {
                                return $result;
                            }
                        }
                    }
                }

                if (isset($data['_identifictor_list']) && (is_array($data['_identifictor_list']) || $data['_identifictor_list'] instanceof Model_Collection_Interface)) {
                    if ($data['_identifictor_list'] instanceof Model_Collection_Interface) {
                        $data['_identifictor_list'] = $data['_identifictor_list']->toArray(true);
                    }

                    if ($userId && !empty($data['_identifictor_list'])) {
                        foreach ($data['_identifictor_list'] as &$item) {
                            if (is_array($item)) {
                                $item['user_id'] = $userId;
                            }
                        }
                    }

                    $_result = new Model_Result();
                    if ($userId && !$importOpts->getChild('identifictor_list')->getAppendLink()) {
                        $opts = IdentifictorModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('user_id', null, 'identifictor') . ' = ?', $userId);
                        IdentifictorModel::getInstance()->deleteIdentifictor($opts);
                    }

                    if ($_result->isValid()) {
                        if (empty($data['_identifictor_list'])) {
                            $result->addChild('identifictor_list', new Model_Result());
                        } else {
                            $_result = IdentifictorModel::getInstance()->importIdentifictorList($data['_identifictor_list'], $importOpts->getChild('identifictor_list'));
                            $result->addChild('identifictor_list', $_result);

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

    
    public function importUserList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importUser($item, $importOpts);
                $result->addChild('user', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addUser($user)
    {
        $userId = null;
        $user = new UserEntity($user);
        $userData = $user->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $userData = $this->addUserFilter($userData);

        $validator = $this->addUserValidate($userData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $userId = $this->insert($this->getTable(), $userData);

                if (!$userId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add User failed', 'add_user_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($userId))
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
            'email' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'phone' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),
            'full_address' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'district' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'region' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'identificator_first' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
                App_Filter::getFilterInstance('Zend_Filter_Null'),
            ),
            'identificator_second' => array(
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
            'dial_rule_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'dial_rule_id' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
                new Zend_Validate_Db_RecordExists(array('adapter' => $this->getDb(), 'table' => 'dial_rule', 'field' => 'id')),  // Существование связи
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
            'email' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 50, 'UTF-8'),  // Проверяем строку
            ),
            'phone' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'phone' в $data
                new Zend_Validate_StringLength(0, 30, 'UTF-8'),  // Проверяем строку
            ),
            'full_address' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 65535, 'UTF-8'),  // Проверяем строку
            ),
            'district' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 200, 'UTF-8'),  // Проверяем строку
            ),
            'region' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 200, 'UTF-8'),  // Проверяем строку
            ),
            'identificator_first' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 50, 'UTF-8'),  // Проверяем строку
            ),
            'identificator_second' => array(
                Zend_Filter_Input::ALLOW_EMPTY => true,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_OPTIONAL,
                new Zend_Validate_StringLength(0, 50, 'UTF-8'),  // Проверяем строку
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

    
    public function addUserFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $user
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'dial_rule_id',
                'surname',
                'name',
                'patronymic',
                'email',
                'phone',
                'full_address',
                'district',
                'region',
                'identificator_first',
                'identificator_second',
                'status',
                'create_date' => date('Y-m-d H:i:s'),

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addUserValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateUser($user, Model_Cond $opts = null)
    {
        $user = new UserEntity($user);
        $userData = $user->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $userData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update User failed', 'update_user_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $userData['id']));
                unset($userData['id']);
            }
        }

        // Фильтруем данные
        $userData = $this->updateUserFilter($userData);

        $validator = $this->updateUserValidate($userData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $userData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateUserByUser($user, $userData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $userIds = $this->getUserIdsFromMixed($user);
        if (!$userIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update User failed', 'update_user_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $userIds));
        
        return $this->updateUser($userData, $opts);
    }

    
    public function updateUserFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $user
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'dial_rule_id',
                'surname',
                'name',
                'patronymic',
                'email',
                'phone',
                'full_address',
                'district',
                'region',
                'identificator_first',
                'identificator_second',
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

    
    public function updateUserValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteUser(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateUser
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteUser(Model_Cond $opts = null)
    {
        return $this->_deleteUser($opts);
    }

    
    public function deleteUserByUser($user, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $user и берем первый
        $userIds = $this->getUserIdsFromMixed($user);

        if (!empty($userIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $userIds));

            $this->_deleteUser($opts);
        }
    }

    
    public function truncateUser()
    {
        $this->truncate();
    }

    
    
    protected function _getUser(Model_Cond $opts = null)
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

                if ($joinEntity == self::JOIN_DIAL_LOG_CALL) {
                    $join->setRule('dial_log_call',
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('user_id', null, 'dial_log_call'),
                                    '');
                    continue;
                }

                if ($joinEntity == self::JOIN_IDENTIFICTOR) {
                    $join->setRule('identifictor',
                                    $this->getTableWithColumnQuoted('id', null, $this->getTable()) . ' = ' . $this->getTableWithColumnQuoted('user_id', null, 'identifictor'),
                                    '');
                    continue;
                }

            }
        }


        // Запускаем запускалку
        return $this->execute($opts->getCond('type', self::FETCH_ROW), null, $opts);
    }

    
    public function getUser(Model_Cond $opts = null)
    {
        return $this->_getUser($opts);
    }

    
    public function getUserByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $userIds = $this->getUserIdsFromMixed($user);
        if (empty($userIds) || (count($userIds) == 1 && reset($userIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $userIds));

        return $this->_getUser($opts);
    }

    
    public function getUserList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getUser($opts->type(self::FETCH_ALL));
    }

    
    public function getUserListByUser($user, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getUserByUser($user, $opts->type(self::FETCH_ALL));
    }

    
    public function getUserCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getUser
        return $this->_getUser($opts->type(self::FETCH_COUNT));
    }

    
    public function existsUserByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $userIds = $this->getUserIdsFromMixed($user);
        if (empty($userIds) || (count($userIds) == 1 && reset($userIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $userIds));

        return $this->_getUser($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getUserByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleIds = DialRuleModel::getInstance()->getDialRuleIdsFromMixed($dialRule);
        if (empty($dialRuleIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('dial_rule_id') => $dialRuleIds));

        return $this->_getUser($opts);
    }

    
    public function getUserListByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByDialRule($dialRule, $opts->type(self::FETCH_ALL));
    }

    
    public function existsUserByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByDialRule($dialRule, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getUserCountByDialRule($dialRule, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByDialRule($dialRule, $opts->type(self::FETCH_COUNT));
    }

    
    public function getUserByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialLogCallList = DialLogCallModel::getInstance()->getDialLogCallListByDialLogCall($dialLogCall);

        $userIds = array();
        foreach($dialLogCallList as $dialLogCall) {
            $userIds[] = $dialLogCall->getUserId();
        }

        $userIds = $this->getUserIdsFromMixed($userIds);
        if (empty($userIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $userIds));

        return $this->_getUser($opts);
    }

    
    public function getUserListByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByDialLogCall($dialLogCall, $opts->type(self::FETCH_ALL));
    }

    
    public function existsUserByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByDialLogCall($dialLogCall, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getUserCountByDialLogCall($dialLogCall, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByDialLogCall($dialLogCall, $opts->type(self::FETCH_COUNT));
    }

    
    public function getUserByIdentifictor($identifictor, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $identifictorList = IdentifictorModel::getInstance()->getIdentifictorListByIdentifictor($identifictor);

        $userIds = array();
        foreach($identifictorList as $identifictor) {
            $userIds[] = $identifictor->getUserId();
        }

        $userIds = $this->getUserIdsFromMixed($userIds);
        if (empty($userIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $userIds));

        return $this->_getUser($opts);
    }

    
    public function getUserListByIdentifictor($identifictor, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByIdentifictor($identifictor, $opts->type(self::FETCH_ALL));
    }

    
    public function existsUserByIdentifictor($identifictor, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByIdentifictor($identifictor, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getUserCountByIdentifictor($identifictor, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByIdentifictor($identifictor, $opts->type(self::FETCH_COUNT));
    }

    
    public function getUserByDialRuleAndPhone($dialRule, $phone, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $dialRuleIds = DialRuleModel::getInstance()->getDialRuleIdsFromMixed($dialRule);
        $phoneIds = $this->_getIdsFromMixed($phone, 'strval');

        if (empty($dialRuleIds) || empty($phoneIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('dial_rule_id') => $dialRuleIds))
             ->where(array($this->getTableWithColumnQuoted('phone') => $phoneIds));

        return $this->getUser($opts);
    }

    
    public function existsUserByDialRuleAndPhone($dialRule, $phone, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getUserByDialRuleAndPhone($dialRule, $phone, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }


    



    
    
    public function prepareUser($data, Model_Cond $opts = null)
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

            if ($opts->checkWith(self::WITH_DIAL_LOG_CALL)) {
                $data['_' . self::WITH_DIAL_LOG_CALL] = DialLogCallModel::getInstance()->getDialLogCallByUser($data['id'], $opts->getWith(self::WITH_DIAL_LOG_CALL)->setEntity('dial_log_call'));
            }

            if ($opts->checkWith(self::WITH_DIAL_LOG_CALL_LIST)) {
                $data['_' . self::WITH_DIAL_LOG_CALL_LIST] = DialLogCallModel::getInstance()->getDialLogCallListByUser($data['id'], $opts->getWith(self::WITH_DIAL_LOG_CALL_LIST)->setEntity('dial_log_call'));
            }

            if ($opts->checkWith(self::WITH_IDENTIFICTOR)) {
                $data['_' . self::WITH_IDENTIFICTOR] = IdentifictorModel::getInstance()->getIdentifictorByUser($data['id'], $opts->getWith(self::WITH_IDENTIFICTOR)->setEntity('identifictor'));
            }

            if ($opts->checkWith(self::WITH_IDENTIFICTOR_LIST)) {
                $data['_' . self::WITH_IDENTIFICTOR_LIST] = IdentifictorModel::getInstance()->getIdentifictorListByUser($data['id'], $opts->getWith(self::WITH_IDENTIFICTOR_LIST)->setEntity('identifictor'));
            }

     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new UserEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareUserList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareUser($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new UserCollection($data);
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

    
    
    public function getUserIdsFromMixed($user)
    {
        if (is_object($user)
            && !$user instanceof UserEntity
            && !$user instanceof UserCollection
            && !$user instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($user);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
