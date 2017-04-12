<?php

class AdminController extends BaseController
{
    public function indexAction()
    {
        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $adminModel = AdminModel::getInstance();

        $opts = $adminModel->getCond();
        $opts->order('id ASC');

        $adminList = $adminModel->getAdminList($opts);

        $this->view->adminList = $adminList;

    }

    public function addAction()
    {
        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $adminModel = AdminModel::getInstance();

        $validateResult = new ValidateResult(AdminModel::$_data);
        if ($this->_request->isPost()){

            $login = $this->getRequest()->getParam('admin_login');
            $password = $this->getRequest()->getParam('admin_password');
            $email = $this->getRequest()->getParam('admin_email');
            $surname = $this->getRequest()->getParam('admin_surname');
            $name = $this->getRequest()->getParam('admin_name');
            $patronymic = $this->getRequest()->getParam('admin_patronymic');

            $data = array(
                'login' => $login,
                'password' => $password,
                'email' => $email,
                'surname' => $surname,
                'name' => $name,
                'patronymic' => $patronymic
            );

            $_result = $adminModel->add($data);
            if ($_result->isError()){
                $resultData = $_result->getResult();

                if ($resultData instanceof ValidateResult) {
                    $validateResult = $resultData;
                }
            }else{
                $this->_redirect($this->view->url('admin_index'));
            }
        }

        $this->getView()->validateResult = $validateResult;
        $this->view->typeArray = AdminModel::$_typeArray;
    }

    public function editAction()
    {
        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $adminModel = AdminModel::getInstance();

        $adminId = $this->getRequest()->getParam('admin_id');
        $isEdit = $this->getRequest()->getParam('act') == $this->edit;
        $isSave = $this->getRequest()->getParam('act') == $this->save;

        $admin = $adminModel->getAdminByAdmin($adminId);
        if (!$admin->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('admin_not_found'), $this->view->url('admin_index'));
            return;
        }

        $validateResult = new ValidateResult($admin->toArray());
        if ($this->_request->isPost() && $admin->exists() && $isSave){
            $login = $this->getRequest()->getParam('admin_login');
            $password = $this->getRequest()->getParam('admin_password');
            $email = $this->getRequest()->getParam('admin_email');
            $surname = $this->getRequest()->getParam('admin_surname');
            $name = $this->getRequest()->getParam('admin_name');
            $patronymic = $this->getRequest()->getParam('admin_patronymic');

            $data = array(
                'login' => $login,
                'password' => $password,
                'email' => $email,
                'surname' => $surname,
                'name' => $name,
                'patronymic' => $patronymic
            );

            $_result = $adminModel->edit($admin, $data);
            if ($_result->isError()){
                $resultData = $_result->getResult();

                if ($resultData instanceof ValidateResult) {
                    $validateResult = $resultData;
                }
            }else{
                $this->_redirect($this->view->url('admin_index'));
            }
        }

        $this->getView()->validateResult = $validateResult;
        $this->view->typeArray = AdminModel::$_typeArray;
        $this->getView()->admin = $admin;
        $this->view->isEdit = $isEdit;
    }

    public function blockAction()
    {
        $this->noRender();

        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $adminModel = AdminModel::getInstance();

        $adminId = $this->getRequest()->getParam('admin_id');

        if ($this->getAdmin()->getId() == $adminId){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('admin_not_found'), $this->view->url('admin_index'));
            return;
        }

        $_result = $adminModel->setStatusBlocked($adminId);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('admin_index'));
            return;
        }

        $this->_redirect($this->view->url('admin_index'));
    }

    public function activeAction()
    {
        $this->noRender();

        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $adminModel = AdminModel::getInstance();

        $adminId = $this->getRequest()->getParam('admin_id');

        if ($this->getAdmin()->getId() == $adminId){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('admin_not_found'), $this->view->url('admin_index'));
            return;
        }

        $_result = $adminModel->setStatusActive($adminId);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('admin_index'));
            return;
        }

        $this->_redirect($this->view->url('admin_index'));
    }

    public function deleteAction()
    {
        $this->noRender();

        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $adminModel = AdminModel::getInstance();

        $adminId = $this->getRequest()->getParam('admin_id');

        if ($this->getAdmin()->getId() == $adminId){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('admin_not_found'), $this->view->url('admin_index'));
            return;
        }

        $_result = $adminModel->setStatusDeleted($adminId);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('admin_index'));
            return;
        }

        $this->_redirect($this->view->url('admin_index'));
    }
}