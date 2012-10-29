<?php

class ZFMultilingual_Core
{
	protected $_localeParameter = null;
	protected $_domain = array();
	protected $_locales = array();
	protected $_translator = null;
	
	protected $_localeRoute = null;
	
	public function __construct($options)
	{
		if (!is_array($options)) {
			/**
			 * @see ZFMultilingual_Exception
			 */
			require_once 'ZFMultilingual/Exception.php';
			throw new ZFMultilingual_Exception('Adapter options array not an array');
		}
		
		$options = array_change_key_case($options, CASE_LOWER);
		
		// Set the locale parameter
		if( isset($options['localeParameter'])) {
			$this->_localeParameter = $options['localeParameter'];
		} else {
			$this->_localeParameter = 'locale';
		}
		
		// Initialize route translations
		if (isset($options['translate'])) {
			$opts = array_change_key_case($options['translate']);
			$regKey = '';
			if (isset($opts['registry_key'])) {
				$regKey = $opts['registry_key'];
			}
			if (empty($regKey)) $regKey = "Zend_Translate_Routes";
			
			$this->_translator = new Zend_Translate($opts);
			Zend_Registry::set($regKey, $this->_translator);
		}
		
		// What locales are available?
		if( isset($options['locales'])) {
			$this->_initLocales($options['locales']);	
		} else {
			// Try to get the locales from the translators!
			$this->_initLocales(null);
		}
		
		// Check if it is a hostname route
		if (isset($options['domain'])) {
			/**
			 * @see ZFMultilingual_Exception
			 */
			require_once 'ZFMultilingual/Exception.php';
			throw new ZFMultilingual_Exception('Domain multilanguage routes have not been implemented yet');
		}
		
		// Initialize the routes
		if (isset($options['routes'])) {
			$this->_initRoutes($options['routes']);	
		} else {
			$this->_initRoutes();
		}
		
	}
	
	
	/**
	 * Initialize the locales.
	 * @param string|array $locales
	 */
	protected function _initLocales($locales)
	{
		if (null !== $locales) {
			if (is_string($locales)) {
				$locales = explode(",", $locales);
				foreach($locales as $locale) {
					$this->_locales = trim($locale);
				}
			} elseif(is_array($locales)) {
				$this->_locales = $locales;
				foreach($locales as $locale) {
					if (!is_string($locale)) {
						/**
						 * @see ZFMultilingual_Exception
						 */
						require_once 'ZFMultilingual/Exception.php';
						throw new ZFMultilingual_Exception('Locales are misconfigured');
					}
				}
			} else {
				/**
				 * @see ZFMultilingual_Exception
				 */
				require_once 'ZFMultilingual/Exception.php';
				throw new ZFMultilingual_Exception('Locales must be defined as an array or a string');
			}
		} else {
		
		}
	}
	
	
	
	/**
	 * Get the locale parameter.
	 * 
	 * This is important for other classes in this library.
	 * 
	 * @return string
	 */
	public function getLocaleParameter()
	{
		return $this->_localeParameter;
	}
	
    /**
     * Get a route frm a config instance
     *
     * @param  Zend_Config $info
     * @return Zend_Controller_Router_Route_Interface
     */
    protected function _getRouteFromConfig(Zend_Config $info)
    {
        $class = (isset($info->type)) ? $info->type : 'Zend_Controller_Router_Route';
        if (!class_exists($class)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($class);
        }

        $route = call_user_func(array($class, 'getInstance'), $info);

        if (isset($info->abstract) && $info->abstract && method_exists($route, 'isAbstract')) {
            $route->isAbstract(true);
        }

        return $route;
    }
    
    /**
     * Add chain routes from a config route
     *
     * @param  string                                 $name
     * @param  Zend_Controller_Router_Route_Interface $route
     * @param  Zend_Config                            $childRoutesInfo
     * @return void
     */
	protected function _addChainRoutesFromConfig($name,
                                                 Zend_Controller_Router_Route_Interface $route,
                                                 Zend_Config $childRoutesInfo)
    {
    	Zend_Controller_Front::getInstance();
    	$router = $front->getRouter();
    	
        foreach ($childRoutesInfo as $childRouteName => $childRouteInfo) {
            if (is_string($childRouteInfo)) {
                $childRouteName = $childRouteInfo;
                $childRoute     = $router->getRoute($childRouteName);
            } else {
                $childRoute = $this->_getRouteFromConfig($childRouteInfo);
            }

            if ($route instanceof Zend_Controller_Router_Route_Chain) {
                $chainRoute = clone $route;
                $chainRoute->chain($childRoute);
            } else {
                $chainRoute = $route->chain($childRoute);
            }

            $chainName = $name . $this->_chainNameSeparator . $childRouteName;

            if (isset($childRouteInfo->chains)) {
                $this->_addChainRoutesFromConfig($chainName, $chainRoute, $childRouteInfo->chains);
            } else {
                $this->addRoute($chainName, $chainRoute);
            }
        }
    }
    
    /**
     * Add a localized route
     * @param unknown_type $name
     * @param unknown_type $route
     */
    public function addRoute($name, $route)
    {
    	// Create the cain
    	$chain = new ZFMultilingual_Controller_Router_Route_Chain($this->_localeParameter);
    	$chain->chain($this->_localeRoute);
    	$chain->chain($route);
    	
    	$front = Zend_Controller_Front::getInstance();
		$router = $front->getRouter();
		$router->addRoute($name, $chain);
    }
    
	/**
	 * Initialize routes
	 */
	protected function _initRoutes($routes = array())
	{
		$front = Zend_Controller_Front::getInstance();
		$router = $front->getRouter();
		$router->removeDefaultRoutes();
		
		// Get default values for the language route
		$defaults = array(
			'module'		=> $front->getDefaultModule(),
			'controller'	=> $front->getDefaultControllerName(),
			'action'		=> $front->getDefaultAction()
		);
		
		
		// Build the requirements for the language route
		$requirements = array();
		
		// ToDo: Build defaults for languages
		// Build requirements for languages
		$langRequirements = $requirements;
		
		
		if (!empty($this->_locales)) {
			$langRequirements[$this->_localeParameter] = "^(" . implode("|", $this->_locales) . ")$";	
		}
			
		// Create the language route
		if (empty($this->_domain)) {
			// The locale is set in the path
			$this->_localeRoute = New Zend_Controller_Router_Route(
				':' . $this->_localeParameter .'/',
				$defaults,
				$langRequirements,
				$this->_translator,
				Zend_locale::getDefault()
			);
		}
		
		unset($langRequirements);
		
		// Create a default module route
		$moduleRoute = new Zend_Controller_Router_Route(
			':@module/:@controller/:@action/*',
			$defaults,
			$requirements,
			$this->_translator,
			Zend_Locale::getDefault()
		);
		
		// Now we chain up the routes
		$this->addRoute('default', $moduleRoute);
		
		// Now we can proceed with the other routes
		foreach ($routes as $name => $info) {
			$info = new Zend_Config($info);
			$route = $this->_getRouteFromConfig($info);
			
			if ($route instanceof Zend_Controller_Router_Route_Chain) {
                if (!isset($info->chain)) {
                    require_once 'Zend/Controller/Router/Exception.php';
                    throw new Zend_Controller_Router_Exception("No chain defined");
                }

                if ($info->chain instanceof Zend_Config) {
                    $childRouteNames = $info->chain;
                } else {
                    $childRouteNames = explode(',', $info->chain);
                }

                foreach ($childRouteNames as $childRouteName) {
                    $childRoute = $router->getRoute(trim($childRouteName));
                    $route->chain($childRoute);
                }

                // Chain with language
                $this->addRoute($name, $route);
            } elseif (isset($info->chains) && $info->chains instanceof Zend_Config) {
                $this->_addChainRoutesFromConfig($name, $route, $info->chains);
            } else {
                $this->addRoute($name, $route);
            }
		}
		
		// Register the plugin
		$front->registerPlugin(new ZFMultilingual_Controller_Plugin_Multilingual($this->_localeParameter));
	}
}