<?php

class GlobalBootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     * Объект запроса
     *
     * @var Zend_Controller_Request_Http
     */
    protected $_request;

    /**
     * Объект ответа
     *
     * @var Zend_Controller_Response_Http
     */
    protected $_response;

    /**
     * @var AdminEntity
     */
    protected $_admin = null;

    /**
     * Название шаблона
     *
     * @var String
     */
    protected $_template = 'default';

    /**
     * View
     *
     * @var $_view Zend_View
     */
    protected $_view  = null;

    public function __construct($application)
    {
        $this->getTimeStart();
        parent::__construct($application);

        umask(0002);
        
        $this->bootstrap('Autoloader');

        Zend_Registry::set('path', new Zend_Config($this->getOption('path') ?: array()));

        $this->bootstrap('Registry');
    }
       
    public function run()
    {
        Zend_Registry::set('Admin', $this->_getAdmin());
        parent::run();
    }

    public function _initAdmin($adminId)
    {
        return AdminModel::getInstance()->getAdminActiveById($adminId);
    }

    /**
     * @return AdminEntity
     */
    protected function _getAdmin()
    {
        if ($this->_admin === null) {
            $this->_admin = $this->getResource('Admin', true);
        }

        return $this->_admin;
    }

    public function _initSession()
    { }
    
    public function _initRegistry()
    {
        $this->bootstrap('Db');

        Zend_Registry::set('Bootstrap', $this);
        Zend_Registry::set('Zend_Db', $this->getResource('Db'));
        Zend_Registry::set('db', $this->getResource('Db'));
    }
    
    public function _initDirs()
    {
        $dirs = new Zend_Config($this->getOption('dir') ?: array());

        Zend_Registry::set('dir',  $dirs);
        Zend_Registry::set('Dirs', $dirs);
        
        putenv('TMP=' . $dirs->tmp);
        
        return $dirs;
    }

    public function _initAsterisk()
    {
        $asterisk = new Zend_Config($this->getOption('asterisk') ?: array());

        Zend_Registry::set('Asterisk',  $asterisk);

        return $asterisk;
    }

    public function _initYandex()
    {
        $yandex = new Zend_Config($this->getOption('yandex') ?: array());

        Zend_Registry::set('Yandex',  $yandex);

        return $yandex;
    }

    public function _initConfigs()
    {
        $configs = new Zend_Config($this->getOption('configs') ?: array());

        Zend_Registry::set('Configs', $configs);

        return $configs;
    }

    public function _initRequest()
    {
        $request = new Zend_Controller_Request_Http();
        $request->setBaseUrl('/');
        Zend_Controller_Front::getInstance()->setRequest($request);

        Zend_Registry::set('Zend_Request', $request);

        $this->_request = $request;

        return $request;
    }

    public function _initResponse()
    {
        $response = new App_Controller_Response_Http();
        Zend_Controller_Front::getInstance()->setResponse($response);

        Zend_Registry::set('Zend_Response', $response);
        $this->_response = $response;

        return $response;
    }

    public function _initTranslator()
    {
        $options = array('route' => array('ru' => 'en'));

        $translator = new Zend_Translate('Array', PROJECT_PATH . '/application/default/localization/base_en.php', 'en', $options);
        $translator->getAdapter()->addTranslation(PROJECT_PATH . '/application/default/localization/base_ru.php', 'ru');
        $translator->getAdapter()->setLocale($this->getResource('Locale', true));

        Zend_Validate_Abstract::setDefaultTranslator($translator);
        Zend_Registry::set('translator', $translator);
        Zend_Registry::set('Zend_Translate', $translator);        

        return $translator;
    }

    /**
     * Инициализация локали
     */
    public function _initLocale()
    {
        $systemLang = Zend_Registry::get('Configs')->system_language;

        $locale = new Zend_Locale();
        $lang = $systemLang;//$this->getRequest()->getCookie('lang');

        if ($lang){
            $locale->setLocale($lang);

        } elseif (empty($lang) || !($lang == 'en' || $lang == 'ru')) {
            try {
                $locale->setLocale(Zend_Locale::BROWSER);
            } catch (Exception $ex) {
                $lang = 'en';
            }
        }

        $locale->setLocale($lang);
        $this->getResponse()->setCookie(new App_Http_Cookie('lang', $locale->getLanguage(), '.' . DOMAIN_ROOT_NAME));

        Zend_Registry::set('Zend_Locale', $locale);
        Zend_Registry::set('locale', $locale);

        return $locale;
    }

    /**
     * Инициализация шаблона
     */
    public function _initTemplate()
    {
        $tempalte = 'default';
        if(!empty($tempalte)) {
            $this->_template = $tempalte;
        }

        $this->getResponse()->setCookie(new App_Http_Cookie('theme', $this->_template, '.' . DOMAIN_ROOT_NAME));

        return $this->_template;
    }

    /**
     * Объект запроса
     *
     * @return Zend_Controller_Request_Http
     */
    public function getRequest()
    {
        $this->bootstrap('Request');
        return $this->_request;
    }

    /**
     * Объект ответа
     *
     * @return App_Controller_Response_Http
     */
    public function getResponse()
    {
        $this->bootstrap('Response');
        return $this->_response;
    }
    
    /**
     * @return boolean
     */
    public function isDevelopment()
    {
        return APPLICATION_ENV == 'development';
    }

    /** 
     * @return boolean
     */
    public function isProduction()
    {
        return APPLICATION_ENV == 'production';
    }

    public function getTimeStart()
    {
        if (!array_key_exists('time_start', $GLOBALS)) {
            $GLOBALS['time_start'] = time();
        }
        return $GLOBALS['time_start'];
    }
    
    /**
     * @param string $name
     * @param bool $bootstrap
     * @return object
     */
    public function getResource($name, $bootstrap = false)
    {
        if ($bootstrap) {
            $this->bootstrap($name);
        }
        return parent::getResource($name);
    }
}
