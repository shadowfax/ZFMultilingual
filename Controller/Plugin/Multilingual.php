<?php

/**
 * Zend Framework Multilingual Site
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   ZFMultilingual
 * @package    ZFMultilingual_Application
 * @subpackage Resource
 * @copyright  Copyright (c) 2005-2012 Juan Pedro Gonzalez Gutierrez (http://www.jpg-consulting.es)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Multilingual.php 24593 2012-01-05 20:35:02Z juan $
 * @author     Juan Pedro Gonzalez Gutierrez
 */

class ZFMultilingual_Controller_Plugin_Multilingual extends Zend_Controller_Plugin_Abstract
{

	protected $_localeParam;
	
	/**
     * Global default translation adapter
     * @var Zend_Translate
     */
    protected static $_translatorDefault;
	
	public function __construct($localeParm)
	{
		$this->_localeParam = $localeParm;
		//parent::__construct();
	}
	
	/**
     * Get global default translator object
     *
     * @return null|Zend_Translate
     */
    public static function getDefaultTranslator()
    {
        if (null === self::$_translatorDefault) {
            require_once 'Zend/Registry.php';
            if (Zend_Registry::isRegistered('Zend_Translate')) {
                $translator = Zend_Registry::get('Zend_Translate');
                if ($translator instanceof Zend_Translate_Adapter) {
                    return $translator;
                } elseif ($translator instanceof Zend_Translate) {
                    return $translator->getAdapter();
                }
            } else {
            	$front = Zend_Controller_Front::getInstance();
            	$bootstrap = $front->getParam('bootstrap');
        		$translator = $bootstrap->getResource('translate');
            	if ($translator instanceof Zend_Translate_Adapter) {
                    return $translator;
                } elseif ($translator instanceof Zend_Translate) {
                    return $translator->getAdapter();
                }
            }
        }
        return self::$_translatorDefault;
    }
    
	public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
    	// Get the locale
        $locale = $request->getParam($this->_localeParam, null);
        
        // Get fron controller
        $front = Zend_Controller_Front::getInstance();
        
        // Get the translator resource
        $translator = $this->getDefaultTranslator();
        
        // Do we have a default translator?
        if (null !== $translator) {
        	if (null === $locale) {
        		$locale = $translator->getLocale();
        	}
        	
        	if (Zend_Locale::isLocale($locale)) {
        		
        		$router = $front->getRouter();
	    		$router->setGlobalParam($this->_localeParam, $locale);
	    			
        		if ($translator->isAvailable($locale)) {
        			// set the locale for the global translator
        			$translator->setLocale($locale);
        			
        			// Set language to global param so that our language route can
	    			// fetch it nicely.
	    			$router = $front->getRouter();
	    			//$router->setGlobalParam($this->_localeParam, $locale);
	    			
	    			// Set the locale
	    			$zendLocale = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('locale');
	    			if ($zendLocale instanceof Zend_Locale) {
	    				$zendLocale->setLocale($locale);
	    			} elseif (Zend_Registry::isRegistered('Zend_Locale')) {
	    				$zendLocale = Zend_Registry::get('Zend_Locale');
	    				if ($zendLocale instanceof Zend_Locale) {
	    					$zendLocale->setLocale($locale);
	    				}
	    			} else {
	    				// create a new locale and register it!
	    				$zendLocale = new Zend_Locale($locale);
	    				Zend_Registry::set('Zend_Locale', $locale);
	    			}

	    			// Set the Content-Language HTTP header to show the current locale.
	    			if ($locale instanceof Zend_Locale) {
	    				$this->_response->setHeader('Content-Language', (string)$locale->getLanguage());
	    			} else {
	    				$locale = new Zend_Locale($locale);
	    				$this->_response->setHeader('Content-Language', (string)$locale->getLanguage());
	    			}
	    			
	    			
	    			// Also set the locale for the route translator
	    			//if (isset($this->_routeTranslatorRegistryKey)) {
	    			//	if (Zend_Registry::isRegistered($this->_routeTranslatorRegistryKey)) {
	    			//		Zend_Registry::get($this->_routeTranslatorRegistryKey)->setLocale($locale);
	    			//	}
	    			//}
        		} else {
        			// The locale is not available!
        			$error = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
					$error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE;
					$error->request = clone $request;
					$error->exception = new Exception('The requested locale is not available');
					$request->setParam('error_handler', $error)
					        ->setModuleName('default')
					        ->setControllerName('error')
					        ->setActionName('error')
					        ->setDispatched(false);
					return;
        		}
        	} else {
        		// It is NOT a locale!
        		// Something went wrong so output an error
				$error = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
				$error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE;
				$error->request = clone $request;
				$error->exception = new Exception('Invalid locale');
				$request->setParam('error_handler', $error)
				        ->setModuleName('default')
				        ->setControllerName('error')
				        ->setActionName('error')
				        ->setDispatched(false);
				return;
        	}
        }
    }
}