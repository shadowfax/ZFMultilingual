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
 * @copyright  Copyright (c) 2005-2012 Juan Pedro Gonzalez Gutierrez (http://www.jgp-consulting.es)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Multilingual.php 24593 2012-10-28 20:35:02Z juan $
 * @author		Juan Pedro Gonzalez Gutierrez
 */

class ZFMultilingual_Application_Resource_Multilingual extends Zend_Application_Resource_ResourceAbstract
{

	protected $_localeParam;
	protected $_mustTranslateLocale;
	
	protected $_translate;
	
	/**
	 * Get the locale parameter in the route string
	 * Enter description here ...
	 */
	protected function getLocaleParam()
	{
		if (!isset($this->_localeParam)) {
			$this->_mustTranslateLocale = false;
			
			$options = $this->getOptions();
			if (isset($options['localeParam'])) {
				$this->_localeParam = trim($options['localeParam']);
				if (strlen($this->_localeParam) == 0) {
					$this->_localeParam = 'locale';
				} elseif(substr($this->_localeParam, 0, 1) == ":") {
					if (strlen($this->_localeParam) > 1) {
						$this->_localeParam = substr($this->_localeParam, 1);
					} else {
						$this->_localeParam = 'locale';
					}
				}
				
				if (substr($this->_localeParam, 0, 1) == "@") {
					
					if (strlen($this->_localeParam) > 1) {
						$this->_localeParam = substr($this->_localeParam, 1);
					} else {
						$this->_localeParam = 'locale';
					}
				}
			} else {
				$this->_localeParam = 'locale';
			}
		}
		
		return $this->_localeParam;
	}
	
	/**
     * Get global default translator object
     *
     * @return null|Zend_Translate
     */
    public static function getDefaultTranslator()
    {
    	//// Get fron controller
        //$front = Zend_Controller_Front::getInstance();
        //
        //// Get the translator resource
        //$bootstrap = $front->getParam('bootstrap');
        //$translator = $bootstrap->getResource('translate');
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
	
	/**
	 * Get the locale requirements
	 * @return string
	 */
	protected function getLocaleRequirements()
	{
		$allowedLocales = null;
		
		$options = $this->getOptions();
		
		if (isset($options['allowedLocales'])) {
			$tempAllowedLocales = $options['allowedLocales'];
			if(!is_array($tempAllowedLocales)) {
				$tempAllowedLocales = explode(",", $tempAllowedLocales);
				foreach($tempAllowedLocales as $key => $value)
				{
					$tempAllowedLocales[$key] = trim($value);
				}
			}
			$allowedLocales = "^(" . implode("|", $tempAllowedLocales) . ")$";
		} elseif(isset($this->_translate)) {
			$languageList = $this->_translate->getList();
			if (null === $languageList) $languageList = array();
			
			// Get also the languages from the default translator if available
			$translator = $this->getDefaultTranslator();
			if (null !== $translator) {
				$temp = $translator->getList();
				if (null !== $temp) $languageList = array_merge($languageList, $temp);
			}
			
			if (count($languageList) > 0) {
				$allowedLocales = "^(" . implode("|", $languageList) . ")$";
			}
		} else {
			// try to override with the default translator
			$translator = $this->getDefaultTranslator();
			if (null !== $translator) {
				$languageList = $translator->getList();
				if (null !== $languageList) {
					$allowedLocales = "^(" . implode("|", $languageList) . ")$";
				}
			}
		}
	
		if (null === $allowedLocales) {
			// Not really nice but better than nothing!
			$allowedLocales = "^([a-z]{2}|[a-z]{2}\_[A-Z]{2})$";
		}
			
		return $allowedLocales;
	}
	
	/**
	 * Return the route default values
	 * @return array
	 */
	protected function getRouteDefaults()
	{
		$front = Zend_Controller_Front::getInstance();
		
		$localeParam = substr($this->_localeParam, 1);
		
		return array(
			'module'		=> $front->getDefaultModule(),
			'controller'	=> $front->getDefaultControllerName(),
			'action'		=> $front->getDefaultAction()
			
		);
	}
	
	/**
	 * Get the translator if available
	 * @return Zend_Translate
	 */
	protected function _initTranslate()
	{
		if (!isset($this->_translate)) {
			$options = $this->getOptions();
			if (isset($options['translate'])) {
				$this->_translate = new Zend_Translate($options['translate']);
			}
		}
		
		return $this->_translate;
	}
	
	protected function setDomainRoute($options)
	{
		throw new Zend_Exception('Domain multilingual routes are not supported at this time.');
	}
	
	protected function setPathRoute()
	{
		$front = Zend_Controller_Front::getInstance();
		
		// locale param name
		$localeParam = $this->getLocaleParam();
		
		$route = new Zend_Controller_Router_Route(
			":" . $localeParam . '/',
			$this->getRouteDefaults(),
			array(
				$localeParam => $this->getLocaleRequirements()
			),
			$this->_translate,
			Zend_Locale::getDefault()
		);
		
		$router = $front->getRouter();
		$router->addRoute('language', $route);
	}
	
	/**
	 * Defined by Zend_Application_Resource_Resource
	 * 
	 * @see Zend_Application_Resource_Resource::init()
	 */
	public function init()
	{
		$bootstrap = $this->getBootstrap();
		$bootstrap->bootstrap('frontController');
				
		// Initialize the translator
		$this->_initTranslate();
		
		$options = $this->getOptions();
		
		if (isset($options['domain'])) {
			if (!is_array($options['domain'])) $this->setDomainRoute(array('name' => $options['domain']));
			else $this->setDomainRoute($options['domain']);	
		} else {
			$this->setPathRoute();	
		}
		
		// ToDo: chain my routes!!!
		
		// Register the plugin
		$front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new ZFMultilingual_Controller_Plugin_Multilingual($this->getLocaleParam()));
	}
}