<?php

abstract class BaseController extends App_Controller_Action
{
    protected static $_admin;
    public $edit = 'edit';
    public $save = 'save';
    public $itemLimit = 20;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
    }

    public function preDispatch()
    {
        $route = null;
        try {
            $route = $this->getRoute();
        } catch (Exception $ex) { }

        if (!$route) {
            return;
        }

        if (!$this->getAdmin()->exists()) {
            if (!$route->getDefault('allow_no_admin')) {
                $adminLastPage = $this->getRequest()->getCookie('AdminLastPage');
                $adminLastPageNew = $this->getRequest()->getRequestUri();
                $urlParts = explode('/', $adminLastPageNew);
                if (!$adminLastPage && strpos(end($urlParts), '.') === false) {
                    $this->setCookie('AdminLastPage', $adminLastPageNew, App_DateInterval::create(0, 5));
                }

                return $this->_redirect($this->view->url('index_login'));
            }
        }
    }

    public function postDispatch()
    {
        $request = $this->getRequest();

        if (Zend_Layout::getMvcInstance()->isEnabled()) {
            $layout = $request->getParam('layout');
            if ($layout) {
                Zend_Layout::getMvcInstance()->setLayout($layout);
            }
        }
    }

    public function init()
    {
        $this->initPager();
    }

    public function initPager()
    {
        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('_pager.phtml');
    }

    /**
     * @param string $name
     * @param string $value
     * @param App_DateTime|App_DateInterval|int $expires Date, interval or UNIX timestamp
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @return BaseController
     */
    public function setCookie($name, $value, $expires = null, $path = null, $domain = null, $secure = false)
    {
        if (!$domain) {
            $domain = '.' . DOMAIN_ROOT_NAME;
        }
        $this->getResponse()->setCookie(new App_Http_Cookie($name, $value, $domain, $expires, $path, $secure));
        return $this;
    }

    /**
     * Action для ошибок
     */
    public function forwardError()
    {
        $this->_forward('error', 'Error');
    }

    public function forwardPermition()
    {
        $this->_forward('permition', 'Error');
    }

    public function forwardForbidden()
    {
        $this->_forward('forbidden', 'Error');
    }

    /**
     * @param array|Model_Result|ValidateResult $message
     * @param null $returlUrl
     */
    public function forwardWarning($message, $returlUrl = null, $label = null)
    {
        $paramMessage = $message;
        if ($message instanceof ValidateResult){
            $paramMessage = $message->getErrorArray();
        }elseif ($message instanceof FunctionResult){
            if ($message->getResult() instanceof ValidateResult){
                $paramMessage = $message->getResult()->getErrorArray();
            }else{
                $paramMessage = array();
                foreach ($message->getErrors() as $code => $error){
                    $paramMessage[] = $error;
                }
            }
        }

        $param = array(
            'message' => $paramMessage,
            'return_url' => $returlUrl,
            'redirect_label' => $label,
            'currentLayout' => $this->getRequest()->getParam('layout')
        );

        $this->_forward('warning', 'Error', null, $param);
    }

    /**
     * @param string $notification
     * @param null $returlUrl
     */
    public function forwardNotification($notification, $returnUrl = null)
    {
        $paramMessage = $notification;
        if ($notification instanceof ValidateResult){
            $paramMessage = $notification->getErrorArray();
        }elseif ($notification instanceof Model_Result){
            if ($notification->getResult() instanceof ValidateResult){
                $paramMessage = $notification->getResult()->getErrorArray();
            }else{
                $paramMessage = array();
                foreach ($notification->getErrorsDecorated()->toArray() as $error){
                    $paramMessage[] = $error['message'];
                }
            }
        }elseif ($notification instanceof TariffPlanSettingsResult){
            $paramMessage = $notification->getMessage();
        }

        $param = array(
            'message' => $paramMessage,
            'return_url' => $returnUrl,
            'currentLayout' => $this->getRequest()->getParam('layout')
        );

        $this->_forward('notification', 'Error', null, $param);
    }

    /**
     * @param AdminEntity $admin
     */
    public static function setAdmin($admin)
    {
        self::$_admin = $admin;
    }

    /**
     *
     * @returm AdminEntity
     */
    public function getAdmin()
    {
        if (!self::$_admin instanceof AdminEntity) {
            self::$_admin = new AdminEntity();
        }

        return self::$_admin;
    }
}