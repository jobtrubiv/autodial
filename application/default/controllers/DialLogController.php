<?php

class DialLogController extends BaseController
{
    public function indexAction()
    {
        $dialLogModel = DialLogModel::getInstance();

        $page = (int) $this->getRequest()->getParam('page', 1);

        $opts = $dialLogModel->getCond();
        $opts->with(DialLogModel::WITH_ADMIN)
            ->with(DialLogModel::WITH_DIAL_RULE)
            ->with(DialLogModel::WITH_DIAL_LOG_CALL_LIST)
            ->limit(7)
            ->page($page, 10)
            ->order('id DESC');

        $dialLogList = $dialLogModel->getDialLogList($opts);

        $countArray = array();
        foreach ($dialLogList as $dialLog){

            $success = 0;
            $fail = 0;
            if ($dialLog->getDialLogCallList()->exists()){

                foreach ($dialLog->getDialLogCallList() as $dialLogCall){
                    if ($dialLogCall->isAnswered()){
                        $success++;
                    }else{
                        $fail++;
                    }
                }
            }

            $countArray[$dialLog->getId()] = array(
                'success' => $success,
                'fail' => $fail,
            );
        }

        $pager = $dialLogList->getPager();

        $this->view->dialLogList = $dialLogList;
        $this->view->countArray = $countArray;
        $this->view->pager = $pager;
    }

    public function infoAction()
    {
        $dialLogModel = DialLogModel::getInstance();

        $dualLogId = $this->getRequest()->getParam('dial_log_id');

        $page = (int) $this->getRequest()->getParam('page', 1);

        $dialLog = $dialLogModel->getDialLogByDialLog($dualLogId);
        if (!$dialLog->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_log_not_found'), $this->view->url('dial_rule_index'));
            return;
        }

        $newfile = Zend_Registry::get('Asterisk')->outgoing;
        $dir = opendir($newfile);
        $count = 0;
        while($file = readdir($dir)){
            if($file == '.' || $file == '..' || is_dir($dir . $file)){
                continue;
            }

            $fileData = file_get_contents($newfile . $file);

            if (strpos($fileData, 'Account: ' . $dialLog->getHash() . '_') !== false){
                $count++;
            }
        }

        if ($count > 0){
            //$this->forwardWarning('Обзвон еще не закончен, дождитесь окончания, осталось пользователей - ' . $count, $this->view->url('dial_log_index'));
            //return;
        }

        $dialLogModel->parseCdr($dialLog);

        $opts = $dialLogModel->getCond();
        $opts->with(DialLogModel::WITH_DIAL_RULE)
            ->with($dialLogModel->getCond(DialLogModel::WITH_DIAL_LOG_CALL_LIST)
            ->with(DialLogCallModel::WITH_USER)
                ->page($page, 10));

        $dialLog = $dialLogModel->getDialLogByDialLog($dualLogId, $opts);

        $pager = $dialLog->getDialLogCallList()->getPager();

        $this->view->dialLog = $dialLog;
        $this->view->pager = $pager;
        $this->view->pageId = $page;
    }

    public function exportAction()
    {
        $dialLogModel = DialLogModel::getInstance();
        $identificatorModel = IdentifictorModel::getInstance();

        $dualLogId = $this->getRequest()->getParam('dial_log_id');

        $opts = $dialLogModel->getCond();
        $opts->with(DialLogModel::WITH_DIAL_RULE)
            ->with($dialLogModel->getCond(DialLogModel::WITH_DIAL_LOG_CALL_LIST)
                ->with(DialLogCallModel::WITH_USER)
                ->order('user_id ASC'));

        $dialLog = $dialLogModel->getDialLogByDialLog($dualLogId, $opts);
        if (!$dialLog->exists()){
            $this->forwardWarning(TranslateModel::getTranslateMessageByCode('dial_log_not_found'), $this->view->url('dial_rule_index'));
            return;
        }

        $result = array();
//№		Номер заказа	Округ	Район	Получатель	Адрес доставки	Номер телефона

        $result[] = array(
            '№',
            'Номер заказа',
            'Округ',
            'Район',
            'Получатель',
            'Адрес доставки',
            'Номер телефона',
            'Продолжительность разговора, сек',
            'Статус ответа',
            'Цифры',
            'Дата вызова',
        );

        $count = 1;
        foreach ($dialLog->getDialLogCallList() as $dialLogCall){
            switch ($dialLogCall->getStatus())
            {
                case 'busy': $status = 'занято'; break;
                case 'answered': $status = 'ответ получен'; break;
                case 'no_answered': $status = 'нет ответа'; break;
                case 'failed': $status = 'сброс вызова'; break;
            }

            $callDigit = $dialLogCall->getCallDigit() ? $dialLogCall->getCallDigitData() : '';

            $result[] = array(
                $count,
                $dialLogCall->getUser()->getIdentificatorFirst(),
                $dialLogCall->getUser()->getDistrict(),
                $dialLogCall->getUser()->getRegion(),
                $dialLogCall->getUser()->getFullName(),
                $dialLogCall->getUser()->getFullAddress(),
                $dialLogCall->getPhone(),
                $dialLogCall->getDuration(),
                $status,
                $callDigit,
                $dialLogCall->getCreateDate()
            );

            $identificatorList = $identificatorModel->getIdentifictorListByUser($dialLogCall->getUser());
            if ($identificatorList->exists()){
                foreach ($identificatorList as $identifictor){
                    $result[] = array(
                        $count,
                        $identifictor->getIdentificator(),
                        $dialLogCall->getUser()->getDistrict(),
                        $dialLogCall->getUser()->getRegion(),
                        $dialLogCall->getUser()->getFullName(),
                        $dialLogCall->getUser()->getFullAddress(),
                        $dialLogCall->getPhone(),
                        $dialLogCall->getDuration(),
                        $status,
                        $callDigit,
                        $dialLogCall->getCreateDate()
                    );
                }
            }

            $count++;
        }

        $this->noRender(true);
        $this->getResponse()->setHeader('Content-type', 'text/csv; charset=utf-8');
        $this->getResponse()->setHeader('Content-disposition', 'attachment; filename=call_list_' . $dialLogCall->getId() . '.csv');

        $output = '';
        $delimiter=';';
        $enclosure='"';
        $escape='\\';
        foreach ($result as $csvRow) {
            $arr = array();
            foreach ($csvRow as $csvCell) {
                $arr[] = $enclosure . str_replace($enclosure, $escape . $enclosure,  $userLine = iconv( "UTF-8", "Windows-1251", $csvCell )) . $enclosure;
            }
            $output .= implode($delimiter, $arr) . "\r\n";
        }

        echo $output;
    }
}