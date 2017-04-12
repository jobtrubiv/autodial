<?php

class DialSettingsController extends BaseController
{
    public function indexAction()
    {
        $dialSettingsModel = DialSettingsModel::getInstance();

        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $dialSettingsList = $dialSettingsModel->getDialSettingsList();

        $this->view->dialSettingsList = $dialSettingsList;
    }

    public function editAction()
    {
        $dialSettingsModel = DialSettingsModel::getInstance();

        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $opts = $dialSettingsModel->getCond();

        $opts->where('active = ?', 'y');

        $dualSettings = $dialSettingsModel->getDialSettings($opts);
        if (!$dualSettings->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_settings_not_found'), $this->view->url('dial_settings_index'));
            return;
        }

        if ($this->_request->isPost()){
            $trunk = $this->getRequest()->getParam('dial_settings_trunk');
            $callerId = $this->getRequest()->getParam('dial_settings_caller_id');
            $context = $this->getRequest()->getParam('dial_settings_context');
            $retryTime = $this->getRequest()->getParam('dial_settings_retry_time');
            $waitTime = $this->getRequest()->getParam('dial_settings_wait_time');

            $data = array(
                'trunk' => $trunk,
                'caller_id' => $callerId,
                'context' => $context,
                'retry_time' => $retryTime,
                'wait_time' => $waitTime,
            );

            $_result = $dialSettingsModel->save($dualSettings, $data);
            if ($_result->isError()){
                $this->forwardWarning($_result, $this->view->url('dial_rule_index'));
                return;
            }

            $this->_redirect($this->view->url('dial_settings_index'));
        }

        $this->view->dualSettings = $dualSettings;
    }

}