<?php

class ErrorController extends BaseController
{
    public function postDispatch()
    {
        parent::postDispatch();

        $this->view->errorController = 1;
    }

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
                return $this->error404Action();
            default:
                // application error 
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->errorCode = 500;
                $this->view->message = 'Application error';
                break;
        }

        $this->view->exception = $errors->exception;
        $this->view->request   = $errors->request;
        $this->render();
    }

    public function error404Action()
    {
        $this->getLayout()->setLayout('error');
        $this->getResponse()->setHttpResponseCode(404);
        $this->view->errorCode = 404;
        $this->view->message = 'Page not found';
        $this->render('error');
    }

    public function permitionAction()
    {
        $this->getLayout()->setLayout('error');
    }

    public function forbiddenAction()
    {
        $this->getResponse()->setHttpResponseCode(404);
        $this->view->errorCode = 404;
        $this->view->message = 'Page not found';
        $this->render('error');
    }

    public function closeAction()
    {
        $this->getLayout()->setLayout('close');
    }

    public function loginAction()
    {
        $this->getLayout()->setLayout('error');
    }

    public function warningAction()
    {
        $this->getLayout()->setLayout('error');

        $message = $this->getRequest()->getParam('message');
        $returnUrl = $this->getRequest()->getParam('return_url');
        $redirectLabel = $this->getRequest()->getParam('redirect_label');
        $currentLayout = $this->getRequest()->getParam('currentLayout');

        $this->view->message = $message;
        $this->view->currentLayout = $currentLayout;
        if ($this->getRequest()->getParam('return_url')){
            $this->view->returnUrl = $returnUrl;
        }

        if ($this->getRequest()->getParam('redirect_label')){
            $this->view->redirectLabel = $redirectLabel;
        }
    }

    public function notificationAction()
    {
        $this->getLayout()->setLayout('error');

        $message = $this->getRequest()->getParam('message');
        $returnUrl = $this->getRequest()->getParam('return_url');
        $currentLayout = $this->getRequest()->getParam('currentLayout');

        $this->view->message = $message;
        $this->view->currentLayout = $currentLayout;
        if ($this->getRequest()->getParam('return_url')){
            $this->view->returnUrl = $returnUrl;
        }
    }
}