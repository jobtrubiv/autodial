<?php

require_once dirname(PROJECT_PATH) . '/library/Zend/Asterisk/Manager.php';

class AsteriskModel
{
    protected static $host;
    protected static $port;
    protected static $userName;
    protected static $secret;
    protected static $asterisk;
    protected static $_instance = null;

    /**************************************************************************************************
     * Set settings
     *************************************************************************************************/

    /**
     * @param string $host
     */
    public static function setHost($host)
    {
        self::$host = $host;
    }

    /**
     * @param int $port
     */
    public static function setPort($port)
    {
        self::$port = $port;
    }

    /**
     * @param string $userName
     */
    public static function setUserName($userName)
    {
        self::$userName = $userName;
    }

    /**
     * @param string $secret
     */
    public static function setSecret($secret)
    {
        self::$secret = $secret;
    }

    /**************************************************************************************************
     * Get settings
     *************************************************************************************************/

    /**
     * @return string
     */
    public static function getHost()
    {
        return self::$host;
    }

    /**
     * @return int
     */
    public static function getPort()
    {
        return self::$port;
    }

    /**
     * @return string
     */
    public static function getUserName()
    {
        return self::$userName;
    }

    /**
     * @return string
     */
    public static function getSecret()
    {
        return self::$secret;
    }

    /**************************************************************************************************
     * Public functions
     *************************************************************************************************/

    /**
     * @param string $phoneNumber
     * @param DialSettingsEntity $dialSettings
     * @param int $dialRuleId
     * @param string $extension
     * @param int $maxRetries
     * @return FunctionResult
     */
    public function originateCall($phoneNumber, $dialSettings, $hash, $extension, $maxRetries = 1, $timeout = 60)
    {
        $result = new FunctionResult();

        self::$asterisk->connect();

        $channel = '';
        if ($dialSettings->getTrunk()){
            $channel = 'SIP/' . $dialSettings->getTrunk() . '/' . $phoneNumber;
        }else{
            $channel = 'SIP/' . $phoneNumber;
        }

        $callData = array(
            'Channel: ' . $channel,
            'Callerid: ' . $dialSettings->getCallerId(),
            'MaxRetries: ' . $maxRetries,
            'RetryTime: ' . $dialSettings->getRetryTime(),
            'WaitTime: ' . $dialSettings->getWaitTime(),
            'Context: ' . $extension,
            'Extension: s',
            'Priority: 1',
            'Account: ' . $hash .  '_' . $phoneNumber,
        );

        $fileSavePath = Zend_Registry::get('dir')->files . '/call/' . $phoneNumber;
        @mkdir(Zend_Registry::get('dir')->files . '/call/', 0777, true);

        file_put_contents($fileSavePath, implode(PHP_EOL, $callData));

        $newfile = Zend_Registry::get('Asterisk')->outgoing . $phoneNumber;
        if (!copy($fileSavePath, $newfile)) {
            $result->add(TranslateModel::getTranslateMessageByCode('copy_file_error'));
            return $result;
        }

        unlink($fileSavePath);

        return $result;
    }

    public function dialPlanReload()
    {
        if(!self::$asterisk->connect()){
            return;
        }

        self::$asterisk->command('dialplan reload');
    }

    public function getQueue()
    {
        if(!self::$asterisk->connect()){
            return;
        }

        $result = self::$asterisk->command('queue show');

        $data = nl2br($result['data']);

        preg_match_all("/([a-zA-Z0-9_]+) has/", $data, $matches);

        return  $matches[1];
    }

    /**************************************************************************************************
     * Instance
     *************************************************************************************************/

    /**
     * @return AsteriskModel
     */
    public static function getInstance()
    {
        if (!self::$_instance instanceof AsteriskModel) {

            $asterisk = Zend_Registry::get('Asterisk');
            if ($asterisk) {
                AsteriskModel::setHost($asterisk->host);
                AsteriskModel::setPort($asterisk->port);
                AsteriskModel::setUserName($asterisk->username);
                AsteriskModel::setSecret($asterisk->secret);

                $config = array(
                    'server' => self::$host,
                    'port' => self::$port,
                    'username' => self::$userName,
                    'secret'   => self::$secret
                );

                self::$asterisk = new Zend_Asterisk_Manager($config);
            }

            self::$_instance = new self;
        }

        return self::$_instance;
    }
}