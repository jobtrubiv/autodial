<?php

class CallFileController extends BaseController
{
    public function indexAction()
    {
        $callFileModel = CallFileModel::getInstance();

        $dialRuleId = $this->getRequest()->getParam('dial_rule_id');

        $opts = $callFileModel->getCond();
        $opts->order('id ASC');

        $callFileList = $callFileModel->getCallFileListByDialRule($dialRuleId, $opts);

        $this->view->callFileList = $callFileList;
        $this->view->dialRuleId = $dialRuleId;
    }

    public function addAction()
    {
        $callFileModel = CallFileModel::getInstance();

        $dialRuleId = $this->getRequest()->getParam('dial_rule_id');

        if ($this->_request->isPost() && isset($_FILES['call_file'])){

            $path = $_FILES["call_file"]["tmp_name"];
            $fileName = $_FILES["call_file"]["name"];

            $sizeMb = round($_FILES['account_file']['size'] / (1024*1024), 2);
            if ($sizeMb > 15){
                $this->forwardWarning(TranslateModel::getTranslateMessageByCode('file_size_limit'), $this->view->url('account_file_index'));
                return;
            }

            $_result = $callFileModel->add($dialRuleId, $this->getAdmin(), $path, $fileName);
            if ($_result->isError()){
                $this->forwardWarning($_result, $this->view->url(array('dial_rule_id' => $dialRuleId), 'call_file_index'));
                return;
            }else{
                $this->_redirect($this->view->url(array('dial_rule_id' => $dialRuleId), 'call_file_index'));
            }
        }
    }

    public function deleteAction()
    {
        $this->noRender();

        $callFileModel = CallFileModel::getInstance();

        $callFileId = $this->getRequest()->getParam('call_file_id');

        $callFile = $callFileModel->getCallFileByCallFile($callFileId);
        if (!$callFile->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('call_file_not_found'), $this->view->url('dial_rule_index'));
            return;
        }

        $_result = $callFileModel->remove($callFileId);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url(array('dial_rule_id' => $callFile->getDialRuleId()), 'call_file_index'));
            return;
        }

        $this->_redirect($this->view->url(array('dial_rule_id' => $callFile->getDialRuleId()), 'call_file_index'));
    }
}