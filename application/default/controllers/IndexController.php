<?php

class IndexController extends BaseController
{
    public function indexAction()
    {
        $dialRuleModel = DialRuleModel::getInstance();

        $opts = $dialRuleModel->getCond();

        $opts->where('status = ?', 'active');

        $dialRuleList = $dialRuleModel->getDialRuleList($opts);

        $this->view->dialRuleList = $dialRuleList;
    }

    public function loginAction()
    {
        $adminModel = AdminModel::getInstance();

        $loginEmail = $this->getRequest()->getParam('index_login');
        $password = $this->getRequest()->getParam('index_password');

        if ($this->_request->isPost()){
            if ($loginEmail && $password){

                $_result = $adminModel->login($loginEmail, $password);            
                if ($_result->isError()){
                    $this->forwardWarning($_result, $this->view->url('index_login'));
                    return;
                }

                /**
                 * @var AdminEntity $admin;
                 */
                $admin = $_result->getResult();

                $this->setCookie('AdminId', $admin->getId());
                $this->setCookie('AdminLastActivity', time());
                $this->setCookie('AdminLastPage', '');

                $this->_redirect($this->view->url('index'));
            }else{
                $this->forwardWarning(TranslateModel::getTranslateMessageByCode('login_password_is_empty'), $this->view->url('index_login'));
                return;
            }
        }
    }

    public function logoutAction()
    {
        $this->setCookie('AdminId', '');
        $this->_redirect($this->view->url('index'));
    }

    public function searchAction()
    {
        $userModel = UserModel::getInstance();

        $text = $this->getRequest()->getParam('query');
        $page = (int) $this->getRequest()->getParam('page', 1);

        if (!$text){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('text_empyu'));
            return;
        }

        //$opts->where("source LIKE '%" . $filterArray['number'] . "%' OR destination LIKE '%" . $filterArray['number'] . "%'");

        $opts = $userModel->getCond();
        $opts->where('status = ?', 'active')
             ->where("surname LIKE '%" . $text . "%' OR name LIKE '%" . $text . " %' OR patronymic LIKE '%" . $text . "%' OR phone LIKE '%" . $text . "%'")
            ->order('id ASC')
            ->page($page, 10);

        $userList = $userModel->getUserList($opts);

        $this->view->userList = $userList;
        $this->view->text = $text;
    }
}