<?php

class DialRuleController extends BaseController
{
    public function indexAction()
    {
        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $dualRuleModel = DialRuleModel::getInstance();

        $opts = $dualRuleModel->getCond();
        $opts->where('status = ?', 'active')
            ->order('id ASC');

        $dialRuleList = $dualRuleModel->getDialRuleList($opts);

        $this->view->dialRuleList = $dialRuleList;
    }

    public function addAction()
    {
        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $dualRuleModel = DialRuleModel::getInstance();

        $validateResult = new ValidateResult($dualRuleModel::$_data);
        if ($this->_request->isPost()){
            $name = $this->getRequest()->getParam('dial_rule_name');
            $maxRetries = $this->getRequest()->getParam('dial_rule_max_retries');
            $timeout = $this->getRequest()->getParam('dial_rule_timeout');

            $data = array(
                'name' => $name,
                'max_retries' => $maxRetries,
                'timeout' => $timeout
            );

            $_result = $dualRuleModel->add($data);
            if ($_result->isError()){
                $resultData = $_result->getResult();

                if ($resultData instanceof ValidateResult) {
                    $validateResult = $resultData;
                }else{
                    $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_rule_found'), $this->view->url('dial_rule_index'));
                    return;
                }
            }else{
                $this->_redirect($this->view->url(array('dial_rule_id' => $_result->getResult()), 'dial_rule_edit'));
            }
        }

        $this->getView()->validateResult = $validateResult;
    }

    public function editAction()
    {
        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $dualRuleModel = DialRuleModel::getInstance();

        $dualRuleId = $this->getRequest()->getParam('dial_rule_id');
        $isEdit = $this->getRequest()->getParam('act') == $this->edit;
        $isSave = $this->getRequest()->getParam('act') == $this->save;

        $opts = $dualRuleModel->getCond();
        $opts->with(DialRuleModel::WITH_DIAL_RULE_PARAMETR_LIST)
            ->with($dualRuleModel->getCond(DialRuleModel::WITH_USER_LIST)
            ->where('status = ?', 'active'));

        $dualRule = $dualRuleModel->getDialRuleByDialRule($dualRuleId, $opts);
        if (!$dualRule->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_rule_found'), $this->view->url('dial_rule_index'));
            return;
        }

        $validateResult = new ValidateResult($dualRule->toArray());
        if ($this->_request->isPost() && $dualRule->exists() && $isSave){
            $name = $this->getRequest()->getParam('dial_rule_name');
            $maxRetries = $this->getRequest()->getParam('dial_rule_max_retries');
            $timeout = $this->getRequest()->getParam('dial_rule_timeout');

            $data = array(
                'name' => $name,
                'max_retries' => $maxRetries,
                'timeout' => $timeout
            );

            $_result = $dualRuleModel->edit($dualRule, $data);
            if ($_result->isError()){
                $resultData = $_result->getResult();

                if ($resultData instanceof ValidateResult) {
                    $validateResult = $resultData;
                }
            }else{
                $this->_redirect($this->view->url(array('dial_rule_id' => $dualRuleId), 'dial_rule_edit'));
            }
        }

        $this->getView()->validateResult = $validateResult;
        $this->getView()->dualRule = $dualRule;
        $this->view->isEdit = $isEdit;
    }

    public function deleteAction()
    {
        $this->noRender();

        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $dualRuleModel = DialRuleModel::getInstance();

        $dualRuleId = $this->getRequest()->getParam('dial_rule_id');

        $_result = $dualRuleModel->setStatusDeleted($dualRuleId);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('dial_rule_index'));
            return;
        }

        $this->_redirect($this->view->url('dial_rule_index'));
    }

    public function activeAction()
    {
        $this->noRender();

        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $dualRuleModel = DialRuleModel::getInstance();

        $dualRuleId = $this->getRequest()->getParam('dial_rule_id');

        $_result = $dualRuleModel->setStatusDeleted($dualRuleId);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('dial_rule_index'));
            return;
        }

        $this->_redirect($this->view->url('dial_rule_index'));
    }

    public function parametrAddAction()
    {
        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $dialRuleParamentModel = DialRuleParametrModel::getInstance();
        $dualRuleModel = DialRuleModel::getInstance();
        $numberActionModel = NumberActionModel::getInstance();
        $callFileModel = CallFileModel::getInstance();

        $dualRuleId = $this->getRequest()->getParam('dial_rule_id');
        $action = $this->getRequest()->getParam('act');

        $dualRule = $dualRuleModel->getDialRuleByDialRule($dualRuleId);
        if (!$dualRule->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'), $this->view->url('dial_rule_index'));
            return;
        }

        if ($this->_request->isPost()){

            if ($action == 'play_file'){
                $actionData = $this->getRequest()->getParam('call_file_id');
            }elseif ($action == 'speech') {
                $actionData = $this->getRequest()->getParam('call_text');
            }elseif ($action == 'digit') {
                $digit = $this->getRequest()->getParam('parametr_digit');
                $digitAction = $this->getRequest()->getParam('parametr_digit_action');
                $digitActionQueue = $this->getRequest()->getParam('parametr_digit_queue');
                $digitActionPlay = $this->getRequest()->getParam('parametr_digit_play');

                $numberAction = $numberActionModel->getNumberActionByCode($digitAction);

                $actionData = $digit . '_' . $numberAction->getId();

                if ($digitActionQueue && $numberAction->getCode() == 'queue'){
                    $actionData .= '_' . $digitActionQueue;
                }elseif($digitActionPlay && $numberAction->getCode() == 'play'){
                    $callFile = $callFileModel->getCallFileByCallFile($digitActionPlay);
                    if ($callFile->exists()){
                        $actionData .= '_p_' . $callFile->getId();
                    }
                }
            }

            $data = array(
                'action' => $action,
                'action_data' => $actionData
            );

            $_result = $dialRuleParamentModel->add($dualRule, $data);
            if ($_result->isError()){
                $this->forwardWarning($_result, $this->view->url(array('dial_rule_id' => $dualRule->getId()),'dial_rule_edit'));
                return;
            }

            $this->_redirect($this->view->url(array('dial_rule_id' => $dualRule->getId()), 'dial_rule_edit'));
        }

        $callFileList = $callFileModel->getCallFileListByDialRule($dualRule);

        $this->view->callFileList = $callFileList;
        $this->view->dialRule = $dualRule;
        $this->view->action = $action;
        $this->view->queueList = AsteriskModel::getInstance()->getQueue();
        $this->view->numberActionList = $numberActionModel->getNumberActionList();
    }

    public function parametrDeleteAction()
    {
        if ($this->getAdmin()->isTypeOperator()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('not_allow'), $this->view->url('admin_index'));
            return;
        }

        $dialRuleParamentModel = DialRuleParametrModel::getInstance();

        $dialRuleParametrId = $this->getRequest()->getParam('dial_rule_parametr_id');

        $dialRuleParametr = $dialRuleParamentModel->getDialRuleParametrByDialRuleParametr($dialRuleParametrId);
        if (!$dialRuleParametr->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_rule_parament_not_found'), $this->view->url('dial_rule_index'));
            return;
        }

        $_result = $dialRuleParamentModel->deleted($dialRuleParametr);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url(array('dial_rule_id' => $dialRuleParametr->getDialRuleId()), 'dial_rule_edit'));
            return;
        }

        $this->_redirect($this->view->url(array('dial_rule_id' => $dialRuleParametr->getDialRuleId()), 'dial_rule_edit'));
    }

    public function playAction()
    {
        $dualRuleModel = DialRuleModel::getInstance();
        $callFileModel = CallFileModel::getInstance();

        $dualRuleId = $this->getRequest()->getParam('dial_rule_id');

        $opts = $dualRuleModel->getCond();
        $opts->with(DialRuleModel::WITH_DIAL_RULE_PARAMETR_LIST);

        $dialRule = $dualRuleModel->getDialRuleByDialRule($dualRuleId, $opts);
        if (!$dialRule->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'), $this->view->url('index'));
            return;
        }

        foreach ($dialRule->getDialRuleParametrList() as $dialRuleParametr) {
            if ($dialRuleParametr->isActionPlayFile()) {

                $callfile = $callFileModel->getCallFileByDialRuleAndHash($dialRule, $dialRuleParametr->getActionData());
                if ($callfile->exists()){
                    $filePath = Zend_Registry::get('dir')->files . $callfile->getHash() . '.wav';
                    if (file_exists(realpath($filePath))){

                        $this->view->test = base64_encode(file_get_contents($filePath));
                    }
                }

            }elseif ($dialRuleParametr->isActionSpeech()){

            }
        }
    }

    public function startAction()
    {
        $dualRuleModel = DialRuleModel::getInstance();

        $dualRuleId = $this->getRequest()->getParam('dial_rule_id');

        $dualRule = $dualRuleModel->getDialRuleByDialRule($dualRuleId);
        if (!$dualRule->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'), $this->view->url('index'));
            return;
        }

        $_result = $dualRuleModel->startDial($dualRule, $this->getAdmin());
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('index'));
            return;
        }

        $this->forwardWarning(TranslateModel::getTranslateMessageByCode('auto_dial_started'), $this->view->url(array('dial_log_id' => $_result->getResult()),'dial_log_info'), 'redirect_to_dial_log');
        return;
    }

    public function startFailAction()
    {
        $dualRuleModel = DialRuleModel::getInstance();
        $dualLogModel = DialLogModel::getInstance();

        $dualLogId = $this->getRequest()->getParam('dial_log_id');

        $dualLog = $dualLogModel->getDialLogByDialLog($dualLogId);
        if (!$dualLog->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_rule_not_found'), $this->view->url('index'));
            return;
        }

        $userArray = array();
        foreach ($this->getRequest()->getParams() as $param => $value){
            if (strpos($param, 'faildialid_') === false){
                continue;
            }

            $logArray = explode('_', $param);

            $userArray[] = $logArray[1];
        }

        $_result = $dualRuleModel->startDial($dualLog->getDialRuleId(), $this->getAdmin(), $userArray);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('index'));
            return;
        }

        $this->forwardWarning(TranslateModel::getTranslateMessageByCode('auto_dial_started'), $this->view->url('index'));
        return;
    }

    public function stopAction()
    {
        $dualLogModel = DialLogModel::getInstance();

        $dualLogId = $this->getRequest()->getParam('dial_log_id');

        $dialLog = $dualLogModel->getDialLogByDialLog($dualLogId);
        if (!$dialLog->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_log_not_found'), $this->view->url('dial_log_index'));
            return;
        }

        $_result = $dualLogModel->stopDial($dialLog);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('dial_log_index'));
            return;
        }

        $this->forwardWarning(TranslateModel::getTranslateMessageByCode('auto_dial_stoped'), $this->view->url(array('dial_log_id' => $dialLog->getId()), 'dial_log_info'));
        return;
    }

    public function stopCallAction()
    {
        $dualLogModel = DialLogModel::getInstance();
        $userModel = UserModel::getInstance();

        $dualLogId = $this->getRequest()->getParam('dial_log_id');
        $userId = $this->getRequest()->getParam('user_id');

        $dialLog = $dualLogModel->getDialLogByDialLog($dualLogId);
        if (!$dialLog->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_log_not_found'), $this->view->url('dial_log_index'));
            return;
        }

        $user = $userModel->getUserByUser($userId);
        if (!$user->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('user_not_found'), $this->view->url(array('dial_log_id' => $dualLogId), 'dial_log_info'));
            return;
        }

        $_result = $dualLogModel->stopDialByUser($dialLog, $user);
        if ($_result->isError()){
            $this->forwardWarning($_result, $this->view->url('dial_log_index'));
            return;
        }

        $this->forwardWarning(TranslateModel::getTranslateMessageByCode('auto_dial_stoped_by_user') . ' ' . $user->getFullName(), $this->view->url(array('dial_log_id' => $dialLog->getId()), 'dial_log_info'));
        return;
    }

}