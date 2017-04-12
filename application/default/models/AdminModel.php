<?php
/**
 * Модель 'Admin'
 */
class AdminModel extends AdminModelAbstract
{
    public static $_data = array(
        'login' => '',
        'password' => '',
        'email' => '',
        'surname' => '',
        'name' => '',
        'patronymic' => '',
    );

    public static $_typeArray = array(
        'admin',
        'operator',
    );

    /**
     * Выборка админа по Id
     *
     * @param int $adminId
     * @return AdminEntity
     */
    public function getAdminById($adminId, Model_Cond $opts = null)
    {
        $opts = $this->_prepareCond($opts);

        if ($adminId == '') {
            return new AdminEntity(null);
        }


        $opts->where('id = ?', $adminId);

        return $this->_getAdmin($opts);
    }

    /**
     * Выборка активного админа по Id
     *
     * @param int $adminId
     * @return AdminEntity
     */
    public function getAdminActiveById($adminId, Model_Cond $opts = null)
    {
        $opts = $this->_prepareCond($opts);

        $opts->where('status = ?', 'active');

        return $this->getAdminById($adminId, $opts);
    }

    /**
     * Авторизация администратора
     *
     * @param string $login
     * @param string $password
     * @return FunctionResult
     */
    public function login($login, $password)
    {
        $result = new FunctionResult();

        $password = EncryptionModel::getInstance(EncryptionModel::ENCRYPTION_TYPE_MD5)->encrypt(trim($password));

        $opts = $this->getCond();
        $opts->where('login = ?', $login)
            ->where('password = ?', $password);

        $admin = $this->getAdmin($opts);
        if (!$admin->exists()){
            $result->add('admin_not_found');
            return $result;
        }

        if ($admin->isBlocked()){
            $result->add('admin_is_bloced');
            return $result;
        }

        if ($admin->isDeleted()){
            $result->add('admin_is_deleted');
            return $result;
        }

        $result->setResult($admin);

        return $result;
    }

    /**
     * @param array $adminData
     * @return FunctionResult
     */
    public function add($adminData)
    {
        $result = new FunctionResult();

        $validateResult = $this->validate($adminData);
        if ($validateResult->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_validate_error'));
            $result->setResult($validateResult);
            return $result;
        }

        $checkAdmin = $this->getAdminByLogin($adminData['login']);
        if ($checkAdmin->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_exists'));
            return $result;
        }

        $adminData['password'] = EncryptionModel::getInstance(EncryptionModel::ENCRYPTION_TYPE_MD5)->encrypt(trim($adminData['password']));

        $_result = $this->importAdmin($adminData);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('import_admin_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|AdminEntity $admin
     * @param array $adminData
     * @return FunctionResult
     */
    public function edit($admin, $adminData)
    {
        $result = new FunctionResult();

        if (!$admin instanceof AdminEntity){
            $admin = $this->getAdminByAdmin($admin);
        }

        if (!$admin->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_not_found'));
            return $result;
        }

        $validateResult = $this->validate($adminData);
        if ($validateResult->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_validate_error'));
            $result->setResult($validateResult);
            return $result;
        }

        if ($adminData['password']) {
            $adminData['password'] = EncryptionModel::getInstance(EncryptionModel::ENCRYPTION_TYPE_MD5)->encrypt(trim($adminData['password']));
        }

        $_result = $this->updateAdminByAdmin($admin, $adminData);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('update_admin_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|AdminEntity $admin
     * @return FunctionResult
     */
    public function setStatusBlocked($admin)
    {
        $result = new FunctionResult();

        if (!$admin instanceof AdminEntity){
            $admin = $this->getAdminByAdmin($admin);
        }

        if (!$admin->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_not_found'));
            return $result;
        }

        if ($admin->isBlocked()){
            return $result;
        }

        $data = array(
            'status' => 'blocked'
        );

        $_result = $this->updateAdminByAdmin($admin, $data);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('blocked_admin_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|AdminEntity $admin
     * @return FunctionResult
     */
    public function setStatusActive($admin)
    {
        $result = new FunctionResult();

        if (!$admin instanceof AdminEntity){
            $admin = $this->getAdminByAdmin($admin);
        }

        if (!$admin->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_not_found'));
            return $result;
        }

        if ($admin->isActive()){
            return $result;
        }

        $data = array(
            'status' => 'active'
        );

        $_result = $this->updateAdminByAdmin($admin, $data);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('blocked_admin_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param int|AdminEntity $admin
     * @return FunctionResult
     */
    public function setStatusDeleted($admin)
    {
        $result = new FunctionResult();

        if (!$admin instanceof AdminEntity){
            $admin = $this->getAdminByAdmin($admin);
        }

        if (!$admin->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('admin_not_found'));
            return $result;
        }

        if ($admin->isDeleted()){
            return $result;
        }

        $data = array(
            'status' => 'deleted'
        );

        $_result = $this->updateAdminByAdmin($admin, $data);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('deleted_admin_error'));
            return $result;
        }

        return $result;
    }

    /**
     * @param array $adminData
     * @return ValidateResult
     */
    public function validate($adminData)
    {
        $validateResult = new ValidateResult($adminData);

        if (empty($adminData['login'])){
            $validateResult->addError('login',  TranslateModel::getTranslateMessageByCode('login_is_not_correct'));
        }else{
            if (preg_match( '/^[a-zA-Z0-9-_\.]{4,20}$/', $adminData['login']) == 0){
                $validateResult->addError('login',  TranslateModel::getTranslateMessageByCode('login_is_not_correct'));
            }
        }

        if (empty($adminData['password'])){
            $validateResult->addError('password',  TranslateModel::getTranslateMessageByCode('password_is_not_correct'));
        }else{
            if (preg_match( '/^[a-zA-Z0-9-_]{4,20}$/', $adminData['password']) == 0){
                $validateResult->addError('password',  TranslateModel::getTranslateMessageByCode('password_is_not_correct'));
            }
        }

        if (!empty($adminData['email'])){
            if (preg_match( '/[-\w.]+@([A-z0-9][-A-z0-9]+\.)+[A-z]{2,4}$/', $adminData['email']) == 0){
                $validateResult->addError('email', TranslateModel::getTranslateMessageByCode('email_is_not_correct'));
            }
        }

        if (empty($adminData['surname'])){
            $validateResult->addError('surname', TranslateModel::getTranslateMessageByCode('surname_is_not_correct'));
        }else{
            if (preg_match( '/^[а-яА-ЯёЁa-zA-Z]/u', $adminData['surname']) == 0){
                $validateResult->addError('surname', TranslateModel::getTranslateMessageByCode('surname_is_not_correct'));
            }
        }

        if (empty($adminData['name'])){
            $validateResult->addError('name', TranslateModel::getTranslateMessageByCode('name_is_not_correct'));
        }else{
            if (preg_match( '/^[а-яА-ЯёЁa-zA-Z]/u', $adminData['surname']) == 0){
                $validateResult->addError('name', TranslateModel::getTranslateMessageByCode('name_is_not_correct'));
            }
        }

        return $validateResult;
    }
}