<?php


class ZFMultilingual_Controller_Router_Route_Module extends Zend_Controller_Router_Route_Abstract
{

	/**
     * Default values for the route (ie. module, controller, action, params)
     * @var array
     */
    protected $_defaults;

    protected $_values      = array();
    protected $_moduleValid = false;
    protected $_keysSet     = false;

    /**#@+
     * Array keys to use for module, controller, and action. Should be taken out of request.
     * @var string
     */
    protected $_moduleKey     = 'module';
    protected $_controllerKey = 'controller';
    protected $_actionKey     = 'action';
    /**#@-*/

    /**
     * @var Zend_Controller_Dispatcher_Interface
     */
    protected $_dispatcher;

    /**
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request;
    
    /**
     * Default translator
     *
     * @var Zend_Translate
     */
    protected static $_defaultTranslator;

    /**
     * Translator
     *
     * @var Zend_Translate
     */
    protected $_translator;

    /**
     * Default locale
     *
     * @var mixed
     */
    protected static $_defaultLocale;

    /**
     * Locale
     *
     * @var mixed
     */
    protected $_locale;
    

    public function getVersion() {
        return 1;
    }

    /**
     * Instantiates route based on passed Zend_Config structure
     */
    public static function getInstance(Zend_Config $config)
    {
        $frontController = Zend_Controller_Front::getInstance();

        $defs       = ($config->defaults instanceof Zend_Config) ? $config->defaults->toArray() : array();
        $dispatcher = $frontController->getDispatcher();
        $request    = $frontController->getRequest();

        return new self($defs, $dispatcher, $request);
    }

    /**
     * Constructor
     *
     * @param array $defaults Defaults for map variables with keys as variable names
     * @param Zend_Controller_Dispatcher_Interface $dispatcher Dispatcher object
     * @param Zend_Controller_Request_Abstract $request Request object
     */
    public function __construct(array $defaults = array(),
                Zend_Controller_Dispatcher_Interface $dispatcher = null,
                Zend_Controller_Request_Abstract $request = null,
                Zend_Translate $translator = null, 
                $locale = null)
    {
        $this->_defaults 	= $defaults;
		$this->_translator  = $translator;
        $this->_locale      = $locale;
        
        if (isset($request)) {
            $this->_request = $request;
        }

        if (isset($dispatcher)) {
            $this->_dispatcher = $dispatcher;
        }
    }

    /**
     * Set request keys based on values in request object
     *
     * @return void
     */
    protected function _setRequestKeys()
    {
        if (null !== $this->_request) {
            $this->_moduleKey     = $this->_request->getModuleKey();
            $this->_controllerKey = $this->_request->getControllerKey();
            $this->_actionKey     = $this->_request->getActionKey();
        }

        if (null !== $this->_dispatcher) {
            $this->_defaults += array(
                $this->_controllerKey => $this->_dispatcher->getDefaultControllerName(),
                $this->_actionKey     => $this->_dispatcher->getDefaultAction(),
                $this->_moduleKey     => $this->_dispatcher->getDefaultModule()
            );
        }

        $this->_keysSet = true;
    }

    /**
     * Matches a user submitted path. Assigns and returns an array of variables
     * on a successful match.
     *
     * If a request object is registered, it uses its setModuleName(),
     * setControllerName(), and setActionName() accessors to set those values.
     * Always returns the values as an array.
     *
     * @param string $path Path used to match against this routing map
     * @return array An array of assigned values or a false on a mismatch
     */
    public function match($path, $partial = false)
    {
    	$translator = $this->getTranslator();
    	if(null !== $translator) {
    		$translateMessages = $translator->getMessages();
    		unset($translator);
    	} else {
    		$translateMessages = array();
    	}
    	
    	
        $this->_setRequestKeys();

        $values = array();
        $params = array();

        if (!$partial) {
            $path = trim($path, self::URI_DELIMITER);
        } else {
            $matchedPath = $path;
        }

        if ($path != '') {
            $path = explode(self::URI_DELIMITER, $path);

            // translate - Module
        	if (($originalPathPart = array_search($path[0], $translateMessages)) !== false) {
                        $path[0] = $originalPathPart;
            }
            if ($this->_dispatcher && $this->_dispatcher->isValidModule($path[0])) {
                $values[$this->_moduleKey] = array_shift($path);
                $this->_moduleValid = true;
            }

	        // translate - Controller
        	if (($originalPathPart = array_search($path[0], $translateMessages)) !== false) {
                        $path[0] = $originalPathPart;
            }
            if (count($path) && !empty($path[0])) {
                $values[$this->_controllerKey] = array_shift($path);
            }

        	// translate - Action
        	if (($originalPathPart = array_search($path[0], $translateMessages)) !== false) {
                        $path[0] = $originalPathPart;
            }
            if (count($path) && !empty($path[0])) {
                $values[$this->_actionKey] = array_shift($path);
            }

            if ($numSegs = count($path)) {
                for ($i = 0; $i < $numSegs; $i = $i + 2) {
                    $key = urldecode($path[$i]);
                	// translate other params
        			if (($originalPathPart = array_search($key, $translateMessages)) !== false) {
                        $key = $originalPathPart;
            		}
                    $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                    $params[$key] = (isset($params[$key]) ? (array_merge((array) $params[$key], array($val))): $val);
                }
            }
        }

        if ($partial) {
            $this->setMatchedPath($matchedPath);
        }

        $this->_values = $values + $params;

        return $this->_values + $this->_defaults;
    }

    /**
     * Assembles user submitted parameters forming a URL path defined by this route
     *
     * @param array $data An array of variable and value pairs used as parameters
     * @param bool $reset Weither to reset the current params
     * @return string Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset = false, $encode = true, $partial = false)
    {
    	$translator = $this->getTranslator();
    	
    	if (isset($data['@locale'])) {
			$locale = $data['@locale'];
			unset($data['@locale']);
		} else {
			$locale = $this->getLocale();
		}
            
        if (!$this->_keysSet) {
            $this->_setRequestKeys();
        }

        $params = (!$reset) ? $this->_values : array();

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $params[$key] = $value;
            } elseif (isset($params[$key])) {
                unset($params[$key]);
            }
        }

        $params += $this->_defaults;

        $url = '';

        if ($this->_moduleValid || array_key_exists($this->_moduleKey, $data)) {
            if ($params[$this->_moduleKey] != $this->_defaults[$this->_moduleKey]) {
            	if (null === $translator) {
                	$module = $params[$this->_moduleKey];
            	} else {
            		$module = $translator->translate($params[$this->_moduleKey], $locale);
            	}
            }
        }
        unset($params[$this->_moduleKey]);

        if (null === $translator) {
        	$controller = $params[$this->_controllerKey];
        } else {
        	$controller = $translator->translate($params[$this->_controllerKey], $locale);
        }
        unset($params[$this->_controllerKey]);

        if (null === $translator) {
        	$action = $params[$this->_actionKey];
        } else {
        	$action = $translator->translate($params[$this->_actionKey], $locale);
        }
        unset($params[$this->_actionKey]);

        foreach ($params as $key => $value) {
        	if (null !== $translator) {
        		$key = $translator->translate($key);
        	}
            $key = ($encode) ? urlencode($key) : $key;
            if (is_array($value)) {
                foreach ($value as $arrayValue) {
                    $arrayValue = ($encode) ? urlencode($arrayValue) : $arrayValue;
                    $url .= self::URI_DELIMITER . $key;
                    $url .= self::URI_DELIMITER . $arrayValue;
                }
            } else {
                if ($encode) $value = urlencode($value);
                $url .= self::URI_DELIMITER . $key;
                $url .= self::URI_DELIMITER . $value;
            }
        }

        if (!empty($url) || $action !== $this->_defaults[$this->_actionKey]) {
            if ($encode) $action = urlencode($action);
            $url = self::URI_DELIMITER . $action . $url;
        }

        if (!empty($url) || $controller !== $this->_defaults[$this->_controllerKey]) {
            if ($encode) $controller = urlencode($controller);
            $url = self::URI_DELIMITER . $controller . $url;
        }

        if (isset($module)) {
            if ($encode) $module = urlencode($module);
            $url = self::URI_DELIMITER . $module . $url;
        }

        return ltrim($url, self::URI_DELIMITER);
    }

    /**
     * Return a single parameter of route's defaults
     *
     * @param string $name Array key of the parameter
     * @return string Previously set default
     */
    public function getDefault($name) {
        if (isset($this->_defaults[$name])) {
            return $this->_defaults[$name];
        }
    }

    /**
     * Return an array of defaults
     *
     * @return array Route defaults
     */
    public function getDefaults() {
        return $this->_defaults;
    }
	
	/**
     * Get the default translator
     *
     * @return Zend_Translate
     */
    public static function getDefaultTranslator()
    {
        return self::$_defaultTranslator;
    }
    
	/**
     * Get the translator
     *
     * @throws Zend_Controller_Router_Exception When no translator can be found
     * @return Zend_Translate
     */
    public function getTranslator()
    {
        if ($this->_translator !== null) {
            return $this->_translator;
        } else if (($translator = self::getDefaultTranslator()) !== null) {
            return $translator;
        } else {
            try {
                $translator = Zend_Registry::get('Zend_Translate');
            } catch (Zend_Exception $e) {
                $translator = null;
            }

            if ($translator instanceof Zend_Translate) {
                return $translator;
            }
        }

        return null;
    }
    
	/**
     * Get the default locale
     *
     * @return mixed
     */
    public static function getDefaultLocale()
    {
        return self::$_defaultLocale;
    }
    
	/**
     * Get the locale
     *
     * @return mixed
     */
    public function getLocale()
    {
        if ($this->_locale !== null) {
            return $this->_locale;
        } else if (($locale = self::getDefaultLocale()) !== null) {
            return $locale;
        } else {
            try {
                $locale = Zend_Registry::get('Zend_Locale');
            } catch (Zend_Exception $e) {
                $locale = null;
            }

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }
}