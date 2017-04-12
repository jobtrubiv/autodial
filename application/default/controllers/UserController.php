<?php

class UserController extends BaseController
{
    public function indexAction()
    {
        $userModel = UserModel::getInstance();

        $dialRuleId = $this->getRequest()->getParam('dial_rule_id');
        $page = (int) $this->getRequest()->getParam('page', 1);

        $opts = $userModel->getCond();
        $opts->with(UserModel::WITH_IDENTIFICTOR_LIST)
            ->where('status = ?', 'active')
            ->order('id ASC')
            ->page($page, 10);


        $userList = $userModel->getUserListByDialRule($dialRuleId, $opts);
        $pager = $userList->getPager();

        $this->view->userList = $userList;
        $this->view->pager = $pager;
        $this->view->dialRuleId = $dialRuleId;
    }

    public function addAction()
    {
        $userModel = UserModel::getInstance();

        $dialRuleId = $this->getRequest()->getParam('dial_rule_id');

        $validateResult = new ValidateResult($userModel::$_data);
        if ($this->_request->isPost()){
            $surname = $this->getRequest()->getParam('user_surname');
            $name = $this->getRequest()->getParam('user_name');
            $patronymic = $this->getRequest()->getParam('user_patronymic');
            $email = $this->getRequest()->getParam('user_email');
            $phone = $this->getRequest()->getParam('user_phone');
            $fullAddress = $this->getRequest()->getParam('user_full_address');
            $district = $this->getRequest()->getParam('user_district');
            $region = $this->getRequest()->getParam('user_region');
            $identificatorFirst = $this->getRequest()->getParam('user_identificator_first');
            $identificatorSecond = $this->getRequest()->getParam('user_identificator_second');

            $data = array(
                'surname' => $surname,
                'name' => $name,
                'patronymic' => $patronymic,
                'email' => $email,
                'phone' => $phone,
                'full_address' => $fullAddress,
                'district' => $district,
                'region' => $region,
                'identificator_first' => $identificatorFirst,
                'identificator_second' => $identificatorSecond,
            );

            $_result = $userModel->add($dialRuleId, $data);
            if ($_result->isError()){
                $resultData = $_result->getResult();

                if ($resultData instanceof ValidateResult) {
                    $validateResult = $resultData;
                }elseif ($resultData instanceof UserEntity){
                    $this->forwardWarning(TranslateModel::getTranslateMessageByCode('Пользователь уже сужествует: ' . $resultData->getFullName()), $this->view->url(array('user_id' => $resultData->getId()), 'user_edit'));
                    return;
                }
            }else{
                $this->_redirect($this->view->url(array('dial_rule_id' => $dialRuleId), 'user_index'));
            }
        }

        $this->getView()->validateResult = $validateResult;
        $this->view->dialRuleId = $dialRuleId;
    }

    public function deleteAllAction()
    {
        $this->noRender();
        $userModel = UserModel::getInstance();

        $dialRuleId = $this->getRequest()->getParam('dial_rule_id');

        $userList = $userModel->getUserListByDialRule($dialRuleId);

        $result = new FunctionResult();
        foreach ($userList as $user){
            $_result = $userModel->setStatusDeleted($user);
            if ($_result->isError()){
                $result->add($_result);
            }
        }


        if ($result->isError()){
            $this->forwardWarning($_result, $this->view->url(array('dial_rule_id' => $user->getDialRuleId()),'user_index'));
            return;
        }

        $this->_redirect($this->view->url(array('dial_rule_id' => $user->getDialRuleId()),'user_index'));
    }

    public function importAction()
    {
        $userModel = UserModel::getInstance();

        $dialRuleId = $this->getRequest()->getParam('dial_rule_id');

        if ($this->_request->isPost()){

            if (!isset($_FILES['import_file'])){
                $this->forwardWarning(TranslateModel::getTranslateMessageByCode('file_not_found'), $this->view->url(array('dial_rule_id' => $dialRuleId), 'user_index'));
                return;
            }

            $filePath = $_FILES['import_file']['tmp_name'];
            $fileName = $_FILES['import_file']['name'];

            $_result = $userModel->importFromFile($dialRuleId, $filePath, $fileName);
            if ($_result->isError()){
                $this->forwardWarning($_result, $this->view->url(array('dial_rule_id' => $dialRuleId), 'user_index'));
                return;
            }

            $this->_redirect($this->view->url(array('dial_rule_id' => $dialRuleId), 'user_index'));

            //$this->forwardWarning(TranslateModel::getTranslateMessageByCode('file_import_sucessfuly'), $this->view->url(array('dial_rule_id' => $dialRuleId), 'user_index'));
            //return;
        }

        $this->view->dialRuleId = $dialRuleId;
    }

    public function editAction()
    {
        $userModel = UserModel::getInstance();
        $dialLogCallModel = DialLogCallModel::getInstance();

        $userId = $this->getRequest()->getParam('user_id');
        $isEdit = $this->getRequest()->getParam('act') == $this->edit;
        $isSave = $this->getRequest()->getParam('act') == $this->save;

        $opts = $userModel->getCond();
        $opts->with(UserModel::WITH_IDENTIFICTOR_LIST);

        $user = $userModel->getUserByUser($userId, $opts);
        if (!$user->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('user_not_found'), $this->view->url('dial_rule_index'));
            return;
        }

        $validateResult = new ValidateResult($user->toArray());
        if ($this->_request->isPost() && $user->exists() && $isSave){
            $surname = $this->getRequest()->getParam('user_surname');
            $name = $this->getRequest()->getParam('user_name');
            $patronymic = $this->getRequest()->getParam('user_patronymic');
            $email = $this->getRequest()->getParam('user_email');
            $phone = $this->getRequest()->getParam('user_phone');
            $fullAddress = $this->getRequest()->getParam('user_full_address');
            $district = $this->getRequest()->getParam('user_district');
            $region = $this->getRequest()->getParam('user_region');
            $identificatorFirst = $this->getRequest()->getParam('user_identificator_first');
            $identificatorSecond = $this->getRequest()->getParam('user_identificator_second');

            $data = array(
                'surname' => $surname,
                'name' => $name,
                'patronymic' => $patronymic,
                'email' => $email,
                'phone' => $phone,
                'full_address' => $fullAddress,
                'district' => $district,
                'region' => $region,
                'identificator_first' => $identificatorFirst,
                'identificator_second' => $identificatorSecond
            );

            $_result = $userModel->edit($user, $data);
            if ($_result->isError()){
                $resultData = $_result->getResult();

                if ($resultData instanceof ValidateResult) {
                    $validateResult = $resultData;
                }

                $this->forwardWarning($_result, $this->view->url(array('dial_rule_id' => $user->getDialRuleId()), 'user_index'));
                return;
            }else{
                $this->_redirect($this->view->url(array('dial_rule_id' => $user->getDialRuleId()), 'user_index'));
            }
        }

        $opts = $dialLogCallModel->getCond();
        $opts->order('create_date ASC');

        $dialLogCallList = $dialLogCallModel->getDialLogCallListByUser($user, $opts);

        $this->view->callCount = $dialLogCallList->count();
        $this->view->firstCall = $dialLogCallList->first()->getCreateDate();
        $this->view->lastCall = $dialLogCallList->last()->getCreateDate();
        $this->view->lastStatus = $dialLogCallList->last()->getStatus();
        $this->view->lastDialLogId = $dialLogCallList->last()->getDialLogId();

        $this->getView()->validateResult = $validateResult;
        $this->getView()->user = $user;
        $this->view->isEdit = $isEdit;
    }

    public function deleteAction()
    {
        $this->noRender();

        $userModel = UserModel::getInstance();

        $userId = $this->getRequest()->getParam('user_id');

        $user = $userModel->getUserByUser($userId);
        if (!$user->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('user_not_found'), $this->view->url('dial_rule_index'));
            return;
        }

        $_result = $userModel->setStatusDeleted($userId);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url(array('dial_rule_id' => $user->getDialRuleId()),'user_index'));
            return;
        }

        $this->_redirect($this->view->url(array('dial_rule_id' => $user->getDialRuleId()),'user_index'));
    }
}