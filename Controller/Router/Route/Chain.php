<?php

/**
 * Zend Framework
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
 * @package    ZFMultilingual_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Chain.php 24593 2012-01-05 20:35:02Z matthew $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Controller_Router_Route_Abstract */
require_once 'Zend/Controller/Router/Route/Abstract.php';

/**
 * Chain route is used for managing route chaining.
 *
 * @package    ZFMultilingual_Controller
 * @subpackage Router
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ZFMultilingual_Controller_Router_Route_Chain extends Zend_Controller_Router_Route_Chain
{
	
	protected $_localeParameter = null;
    
	public function __construct($localeParameter)
	{
		$this->_localeParameter = $localeParameter;
	}
    
    /**
     * Assembles a URL path defined by this route
     *
     * @param array $data An array of variable and value pairs used as parameters
     * @return string Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset = false, $encode = false)
    {
        $value     = '';
        $numRoutes = count($this->_routes);
        
        // Set the locale
        if (!isset($data['@locale'])) {
        	if (isset($data[$this->_localeParameter])) {
        		$data['@locale'] = $data[$this->_localeParameter];
        	}
        }

        foreach ($this->_routes as $key => $route) {
            if ($key > 0) {
                $value .= $this->_separators[$key];
            }

            $value .= $route->assemble($data, $reset, $encode, (($numRoutes - 1) > $key));

            if (method_exists($route, 'getVariables')) {
                $variables = $route->getVariables();

                foreach ($variables as $variable) {
                	$data[$variable] = null;
                }
            }
        }

        return $value;
    }
}
