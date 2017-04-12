<?php

$GLOBALS['time_start'] = microtime(true);

define('PROJECT_PATH', realpath(dirname(__FILE__) . '/../'));
define('LIBRARY_PATH', realpath(PROJECT_PATH . '/library/'));

define('LIBRARY_APP_PATH', getenv('LIBRARY_APP_PATH'));
define('APPLICATION_ENV', 'production');
define('LIBRARY_MODEL_PATH', getenv('LIBRARY_MODEL_PATH'));
define('DOMAIN_ROOT_NAME_PRODUCTION', getenv('DOMAIN_ROOT_NAME_PRODUCTION'));

set_include_path(LIBRARY_MODEL_PATH .
    PATH_SEPARATOR . LIBRARY_APP_PATH .
    PATH_SEPARATOR .LIBRARY_PATH .
    PATH_SEPARATOR . get_include_path());

try {

    require_once 'Zend/Loader/Autoloader.php';
    $loader = Zend_Loader_Autoloader::getInstance();
    $loader->registerNamespace('App_')
        ->registerNamespace('Model_')
        ->registerNamespace('App_');
    $loader->setFallbackAutoloader(true);
    
    $request = new Zend_Controller_Request_Http();
    $httpHost = $request->getHttpHost();

    $isApi = false;
    $domain = str_replace(array('http://www.', 'http://'), '', $httpHost);
    $domainRoot = DOMAIN_ROOT_NAME_PRODUCTION;
    
    if (preg_match('#^((([^\.]+)\.)?([^\.]+)\.)?(\w+)\.(\w{1,6})$#is', $domain, $m)) {
        if (APPLICATION_ENV != 'production') {
            $domainRoot = $m[5] . '.' . $m[6];
        }
        $domain = $domainRoot;
    } else {
        throw new Exception("Wrong domain name");
    }
    
    define('DOMAIN_ROOT_NAME', $domainRoot);
    define('DOMAIN_NAME', $domain);

    define('APPLICATION_PATH', realpath(PROJECT_PATH . '/application/default'));
    Zend_Controller_Front::getInstance()->setControllerDirectory(APPLICATION_PATH . '/controllers/');

    $application = new Zend_Application(APPLICATION_ENV, PROJECT_PATH . '/application/default/configs/application.ini');
    Zend_Registry::set('application', $application);

    $application->bootstrap()->run();
} catch (Exception $e) {
    echo "Server error";
}
