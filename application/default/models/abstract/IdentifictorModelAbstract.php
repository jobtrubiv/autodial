<?php


abstract class IdentifictorModelAbstract extends Model_Db_Mysql_Abstract
{
    const WITH_USER = 'user';
    const JOIN_USER = 'user';
    protected $_filterRules;

    protected function _setupTableName()
    {
        $this->_table = 'identifictor';
    }

    
    
    public function importIdentifictor($data, Model_Import_Cond $importOpts = null)
    {
        if (!$importOpts instanceof Model_Import_Cond) {
            $importOpts = new Model_Import_Cond();
        }

        $identifictorId = null;

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
            if (!$identifictorId && array_key_exists('id', $data)) {
                $identifictorId = IdentifictorModel::getInstance()->existsIdentifictorByIdentifictor($data['id']);
            }

            // Если продукта еще нет,
            // то обязательно нужно проверить поля без которых он не может быть добавлен
            // Та же логика должна быть когда разрешен каскад

            if (array_key_exists('user_id', $data)) {
                $_data['user_id'] = $data['user_id'];
            }

            // Связь обязательная, забиваем на CascadeAllowed
            if (((!$identifictorId && !array_key_exists('user_id', $data)) || $importOpts->getCascadeAllowed()) && array_key_exists('_user', $data) && !empty($data['_user'])) {
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

            if (!$identifictorId) {
                try {
                    $_result = $this->addIdentifictor($data);
                    if ($_result->isError()) {
                        throw new Exception($_result->getErrorsDecorated()->toString());
                    }
                    $identifictorId = $_result->getResult();
                    $result->setValidator($_result->getValidator());
                } catch (Exception $ex) {
                    $result->addChild('general', $this->getGeneralErrorResult('Import Identifictor failed: ' . $ex->getMessage(), 'import_identifictor_failed'));
                }
            } elseif ($importOpts->getUpdateAllowed()) {
                $_result = $this->updateIdentifictor($data, IdentifictorModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $identifictorId));
                $result->setValidator($_result->getValidator());
            } elseif (!empty($_data) && $importOpts->getCascadeAllowed()) {
                $_result = $this->updateIdentifictor($_data, IdentifictorModel::getInstance()->getCond()->where($this->getTableWithColumnQuoted('id') . ' = ?', $identifictorId));
                $result->setValidator($_result->getValidator());
            }

            $result->setResult(intval($identifictorId));

            if (!$identifictorId && !$importOpts->getIgnoreErrors()) {
                return $result;
            }

            if (($identifictorId || $importOpts->getIgnoreErrors()) && $importOpts->getCascadeAllowed()) {


            }

        }

        return $result;
    }

    
    public function importIdentifictorList($data, Model_Import_Cond $importOpts = null)
    {
        $result = new Model_Result();
        $resultIds = array();

        if ($data instanceof Model_Collection_Interface) {
            $data = $data->toArray(true);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->importIdentifictor($item, $importOpts);
                $result->addChild('identifictor', $_result);
                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);

        return $result;
    }


    
    
    public function addIdentifictor($identifictor)
    {
        $identifictorId = null;
        $identifictor = new IdentifictorEntity($identifictor);
        $identifictorData = $identifictor->toArray();
        $result = new Model_Result();
        
        // Фильтруем данные
        $identifictorData = $this->addIdentifictorFilter($identifictorData);

        $validator = $this->addIdentifictorValidate($identifictorData);

        // Если добавляемые данные верны
        if ($validator->isValid()) {
            try {
                // Добавляем и запоминаем ID добавленной записи
                $identifictorId = $this->insert($this->getTable(), $identifictorData);

                if (!$identifictorId) {
                    // Если валидатор пропустил, а данные все равно не вставились
                    // регистрируем в валидаторе generalError
                    $result->addChild('general', $this->getGeneralErrorResult('Add Identifictor failed', 'add_identifictor_failed'));
                }
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setResult(intval($identifictorId))
               ->setValidator($validator);
               
        return $result;
    }

    
    public function getFilterRules()
    {
        if ($this->_filterRules != null) {
            return $this->_filterRules;
        }
        
        $this->_filterRules = array(
            'user_id' => array(
                App_Filter::getFilterInstance('Zend_Filter_Int'),  // Делаем integer
            ),
            'identificator' => array(
                App_Filter::getFilterInstance('App_Filter_StringTrim'), // Удаляем херню побокам
            ),

        );

        return $this->_filterRules;
    }

    
    protected function getValidatorRules($optionalPresence = false)
    {
        $presence = $optionalPresence ? Zend_Filter_Input::PRESENCE_OPTIONAL : null;

        $validators = array(
            'user_id' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'user_id' в $data
                new Zend_Validate_Int(),  // Проверяем на integer
                new Zend_Validate_Db_RecordExists(array('adapter' => $this->getDb(), 'table' => 'user', 'field' => 'id')),  // Существование связи
            ),
            'identificator' => array(
                Zend_Filter_Input::ALLOW_EMPTY => false,  // Разрешено ли пустое значение
                Zend_Filter_Input::PRESENCE => $presence ?: Zend_Filter_Input::PRESENCE_REQUIRED,  // Будет ошибка если нет ключа 'identificator' в $data
                new Zend_Validate_StringLength(0, 200, 'UTF-8'),  // Проверяем строку
            ),

        );

        return $validators;
    }

    
    public function addIdentifictorFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $identifictor
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'user_id',
                'identificator',

        );

        $_data = $this->getDataValues($data, $defaults);

        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();       

        return $_data;
    }

    
    public function addIdentifictorValidate($data)
    {
        $validators = $this->getValidatorRules();

        return $this->runValidator($data, $validators);
    }

    
    public function updateIdentifictor($identifictor, Model_Cond $opts = null)
    {
        $identifictor = new IdentifictorEntity($identifictor);
        $identifictorData = $identifictor->toArray();
        $result = new Model_Result();

        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Если нет ни where ни идентификатора, то ничего не делаем
        // ибо не знаем как обновлять данные
        if (!$this->_checkWhere($opts)) {
            if (!array_key_exists('id', $identifictorData)) {                                                                     
                $result->addChild('general', $this->getGeneralErrorResult('Update Identifictor failed', 'update_identifictor_failed'));
                return $result;
            } else {
                $opts->where(array($this->getTableWithColumnQuoted('id') => $identifictorData['id']));
                unset($identifictorData['id']);
            }
        }

        // Фильтруем данные
        $identifictorData = $this->updateIdentifictorFilter($identifictorData);

        $validator = $this->updateIdentifictorValidate($identifictorData);

        // Если изменяемые данные верны
        if ($validator->isValid()) {
            try {
                // Изменяем данные
                $this->update($this->getTable(), $identifictorData, $opts);
            } catch (Exception $ex) {
                $result->addChild('exception', $this->getGeneralErrorResult($ex->getMessage()));
            }
        }

        $result->setValidator($validator);
        
        // Возвращаем результат операции
        return $result;
    }

    
    public function updateIdentifictorByIdentifictor($identifictor, $identifictorData, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);
        
        $identifictorIds = $this->getIdentifictorIdsFromMixed($identifictor);
        if (!$identifictorIds) {
            $result = new Model_Result();
            $result->addChild('general', $this->getGeneralErrorResult('Update Identifictor failed', 'update_identifictor_failed'));
            return $result;
        }
        
        $opts->where(array($this->getTableWithColumnQuoted('id') => $identifictorIds));
        
        return $this->updateIdentifictor($identifictorData, $opts);
    }

    
    public function updateIdentifictorFilter($data)
    {
        // Прописываем значения по умолчанию и что нужно взять с $identifictor
        // Если определен и ключ и значение, это значит 'ЧтоВзять' => 'ЕслиНеБудетТоБеремЭто'
        $defaults = array(
                'user_id',
                'identificator',

        );

        $_data = $this->getDataValues($data, $defaults);


        if (empty($_data)) {
            return array();
        }
        
        $_data = $this->runValidator($_data, null, $this->getFilterRules())->getUnescaped();

        return $_data;
    }

    
    public function updateIdentifictorValidate($data)
    {
        $validators = $this->getValidatorRules(true);

        return $this->runValidator($data, $validators);
    }

    
    protected function _deleteIdentifictor(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Еcли WHERE пустой - ошибка, функция удаляющая все называется truncateIdentifictor
        if (!$this->_checkWhere($opts)) {
            return false;
        }

        try {
            return $this->delete($this->getTable(), $opts);
        } catch (Exception $ex) {
            return false;
        }
    }

    
    public function deleteIdentifictor(Model_Cond $opts = null)
    {
        return $this->_deleteIdentifictor($opts);
    }

    
    public function deleteIdentifictorByIdentifictor($identifictor, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Пытаемся выдернуть ID'шники с $identifictor и берем первый
        $identifictorIds = $this->getIdentifictorIdsFromMixed($identifictor);

        if (!empty($identifictorIds)) {
            // Берем из $opts текущий Zend_Db_Select и даписываем условие
            $opts->where(array($this->getTableWithColumnQuoted('id') => $identifictorIds));

            $this->_deleteIdentifictor($opts);
        }
    }

    
    public function truncateIdentifictor()
    {
        $this->truncate();
    }

    
    
    protected function _getIdentifictor(Model_Cond $opts = null)
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

    
    public function getIdentifictor(Model_Cond $opts = null)
    {
        return $this->_getIdentifictor($opts);
    }

    
    public function getIdentifictorByIdentifictor($identifictor, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $identifictorIds = $this->getIdentifictorIdsFromMixed($identifictor);
        if (empty($identifictorIds) || (count($identifictorIds) == 1 && reset($identifictorIds) == null)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $identifictorIds));

        return $this->_getIdentifictor($opts);
    }

    
    public function getIdentifictorList(Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->_getIdentifictor($opts->type(self::FETCH_ALL));
    }

    
    public function getIdentifictorListByIdentifictor($identifictor, Model_Cond $opts = null)
    {
        // Делаем обработку $opts Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        return $this->getIdentifictorByIdentifictor($identifictor, $opts->type(self::FETCH_ALL));
    }

    
    public function getIdentifictorCount(Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        // Обращаемся к главному - _getIdentifictor
        return $this->_getIdentifictor($opts->type(self::FETCH_COUNT));
    }

    
    public function existsIdentifictorByIdentifictor($identifictor, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $identifictorIds = $this->getIdentifictorIdsFromMixed($identifictor);
        if (empty($identifictorIds) || (count($identifictorIds) == 1 && reset($identifictorIds) == null)) {
            return null;
        }

        $opts->where(array($this->getTableWithColumnQuoted('id') => $identifictorIds));

        return $this->_getIdentifictor($opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getIdentifictorByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        $userIds = UserModel::getInstance()->getUserIdsFromMixed($user);
        if (empty($userIds)) {
            return $opts->getEmptySelectResult();
        }

        $opts->where(array($this->getTableWithColumnQuoted('user_id') => $userIds));

        return $this->_getIdentifictor($opts);
    }

    
    public function getIdentifictorListByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getIdentifictorByUser($user, $opts->type(self::FETCH_ALL));
    }

    
    public function existsIdentifictorByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getIdentifictorByUser($user, $opts->columns(array('id'))->type(self::FETCH_ONE));
    }

    
    public function getIdentifictorCountByUser($user, Model_Cond $opts = null)
    {
        // Подготавливаем работу с опциями
        $opts = $this->_prepareCond($opts);

        return $this->getIdentifictorByUser($user, $opts->type(self::FETCH_COUNT));
    }


    



    
    
    public function prepareIdentifictor($data, Model_Cond $opts = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_ENTITY, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }
        
        if (!empty($data)) {
            if ($opts->checkWith(self::WITH_USER)) {
                $data['_' . self::WITH_USER] = UserModel::getInstance()->getUserByUser($data['user_id'], $opts->getWith(self::WITH_USER)->setEntity('user'));
            }

     }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                return new IdentifictorEntity($data);
            case Model_Cond::PREPARE_ARRAY:
                return (array)$data;
            default:
                if (!class_exists($returnType)) {
                    throw new Model_Exception("Class '{$returnType}' not found");
                }
                return new $returnType($data);
        }
    }

    
    public function prepareIdentifictorList($data, Model_Cond $opts = null, $pager = null)
    {
        // Делаем обработку $opts. Представь, что если пришел null?
        $opts = $this->_prepareCond($opts);

        $returnType = $opts->getCond(Model_Cond::PREPARE_COLLECTION, Model_Cond::PREPARE_DEFAULT);
        if ($returnType == Model_Cond::PREPARE_DISABLE) {
            return $data;
        }

        foreach ($data as &$item) {
            $item = $this->prepareIdentifictor($item, $opts);
        }

        switch ($returnType) {
            case Model_Cond::PREPARE_DEFAULT:
                $result = new IdentifictorCollection($data);
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

    
    
    public function getIdentifictorIdsFromMixed($identifictor)
    {
        if (is_object($identifictor)
            && !$identifictor instanceof IdentifictorEntity
            && !$identifictor instanceof IdentifictorCollection
            && !$identifictor instanceof Model_Result
        ) {
            return array();
        }
        return self::_getIdsFromMixed($identifictor);
    }
        
    
    public static function getInstance($type = null)
    {
        return parent::getInstance($type);
    }
    
}
