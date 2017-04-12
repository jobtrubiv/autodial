<?php
                 
require_once __DIR__ . '/../GlobalBootstrap.php';
require_once __DIR__ . '/controllers/BaseController.php';

/**
 * default/Bootstrap.php
 */
class Bootstrap extends GlobalBootstrap
{
    public function __construct($application)
    {
        parent::__construct($application);
    }
    
    public function run()
    {
        BaseController::setAdmin($this->_getAdmin());

        parent::run();
    }

    public function _initAdmin($adminId = '')
    {
        $adminId = $this->getRequest()->getCookie('AdminId');

        $admin = parent::_initAdmin($adminId);
        $adminLastActivity = intval($this->getRequest()->getCookie('AdminLastActivity'));

        $time = new Zend_Config($this->getOption('time') ?: array());
        $autoLogoutMinutes = $time->autoLogoutMinutes ?: 5;
        if (!$this->getRequest()->getCookie("AdminAutoLogoutMinutes") && $this->getResponse()->canSendHeaders()) {
            $this->getResponse()->setCookie(new App_Http_Cookie('AdminAutoLogoutMinutes', $autoLogoutMinutes, '.' . DOMAIN_ROOT_NAME));
        }

        if (time() - $adminLastActivity < $autoLogoutMinutes*60) {
            if ($admin->exists() && $this->getResponse()->canSendHeaders()) {
                $this->getResponse()->setCookie(new App_Http_Cookie('AdminLastActivity', time(), '.' . DOMAIN_ROOT_NAME));
            }
        } else {
            $admin = new AdminEntity();
        }

        return $admin;
    }

    public function _initDirs()
    {
        $dirs = parent::_initDirs();
        return $dirs;
    }

    public function _initSession()
    {

    }

    public function _initView()
    {
        $view = new App_View();
        $style = $this->_initTemplate();

        $view->addScriptPath(APPLICATION_PATH . '/views/scripts/' . $style);
        $view->addHelperPath(LIBRARY_APP_PATH . '/App/View/Helper/', 'App_View_Helper_');
        $view->addHelperPath(APPLICATION_PATH . '/views/helpers/', 'App_View_Helper_');
        $view->headTitle()->setSeparator(' — ');

        $view->env = APPLICATION_ENV;
        $view->request = $this->getRequest();
        $view->lang = $this->getRequest()->getCookie('ru');
        $view->theme = $this->getRequest()->getCookie('theme');
        $view->currentAdmin = $this->_getAdmin();
        $view->dir = $this->getResource('Dirs', true);
        $view->errorMessage = new Model_Result();
        $view->validateResult = new ValidateResult();
        $view->filterArray = array();
        $view->dialLogResultList = $this->getDialLog();

        Zend_Registry::set('Zend_View', $view);
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        return $view;
    }

    /**
     * @return DialLogCollection|DialLogEntity[]
     */
    public function getDialLog()
    {
        $dialLogModel = DialLogModel::getInstance();

        $opts = $dialLogModel->getCond();
        $opts->with(DialLogModel::WITH_ADMIN)
            ->with(DialLogModel::WITH_DIAL_RULE)
            ->limit(3)
            ->order('id DESC');

        $dialLogList = $dialLogModel->getDialLogList($opts);

        return $dialLogList;
    }

    public function _initRouter()
    {
        $frontController = $this->getResource('FrontController', true);

        $router = $frontController->getRouter();
        $router->removeDefaultRoutes();

        $routesConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routes.ini', null, array('allowModifications' => true));
        $routesSystemConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routes_system.ini', null, array('allowModifications' => true));
        $router->addConfig($routesConfig);
        $router->addConfig($routesSystemConfig);
        
        return $router;
    }

    public function _initLayout()
    {
        if ($this->_layout == null) {                        
            $style = $this->_initTemplate();
            $options = array('layoutPath' => APPLICATION_PATH . '/layouts/' . $style . '/',
                             'layout'     => 'main');

            $this->bootstrap('FrontController');

            $this->_layout = Zend_Layout::startMvc($options);
        }

        return $this->_layout;
    }
}