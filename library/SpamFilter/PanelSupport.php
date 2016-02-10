<?php
/*
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering				*
*                                                                       *
* Copyright (c) 2010-2011 SpamExperts B.V. All Rights Reserved,         *
*                                                                       *
*************************************************************************
*                                                                       *
* Email: support@spamexperts.com                                        *
* Website: htttp://www.spamexperts.com                                  *
*                                                                       *
*************************************************************************
*                                                                       *
* This software is furnished under a license and may be used and copied *
* only in accordance with the  terms of such license and with the       *
* inclusion of the above copyright notice. No title to and ownership    *
* of the software is  hereby  transferred.                              *
*                                                                       *
* You may not reverse engineer, decompile or disassemble this software  *
* product or software product license.                                  *
*                                                                       *
* SpamExperts may terminate this license if you don't comply with any   *
* of the terms and conditions set forth in our end user                 *
* license agreement (EULA). In such event, licensee agrees to return    *
* licensor  or destroy  all copies of software upon termination of the  *
* license.                                                              *
*                                                                       *
* Please see the EULA file for the full End User License Agreement.     *
*                                                                       *
*************************************************************************
* @category  SpamExperts
* @package   ProSpamFilter
* @author    $Author$
* @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
* @license   Closed Source
* @version   3.0
* @link      https://my.spamexperts.com/kb/34/Addons
* @since     2.0
*/
class SpamFilter_PanelSupport
{
	/**
	 * @param $_paneltype Type of panel used
	 */
        var $_paneltype;

	/**
	 * @param $_class Unused
	 */
        public $_class;

	/**
	 * @var string $_classname Unused
	 */
        var $_classname;
        /**
         * __construct
         * Constructs the panelsupport system and check whether that panel exists
         *
         * @param string $paneltype Override paneltype. If none provided, auto guessing is applied
         *
         * @return bool Status
         *
	 * @access public
         */
        public function __construct( $paneltype = null, $options = array() )
        {
			if(!isset($paneltype))
			{
				$paneltype = SpamFilter_Core::getPanelType();
				Zend_Registry::get('logger')->debug("[PanelSupport] Paneltype has been determined to be '{$paneltype}'");
			} else {
				Zend_Registry::get('logger')->debug("[PanelSupport] Overriding panelsupport detection with manual entry '{$paneltype}'");
			}

			$paneltype = ucfirst( strtolower( $paneltype ) );
			$this->_paneltype = $paneltype;

			$classname = "SpamFilter_PanelSupport_$paneltype";
			if(!class_exists($classname))
			{
				throw new exception("The selected panel ({$paneltype}) does not exist");
				return false;
			}

			Zend_Registry::get('logger')->debug("[PanelSupport] Creating class {$classname} with options: " . serialize($options) );
			$this->_classname = $classname;
			$this->_class = new $classname( $options );
        }

        /**
         * __call
         * Magic function to redirect calls to appropriate "driver"
         *
         * @param $name Call
         * @param $params Parameters used (if any)
         *
		 * @todo Rework this system so that it does not *require* an array input
		 *
         * @return mixed|bool Return output from function|Failed status
         *
		 * @access public
         */
		public function __call($name, $params)
		{
			Zend_Registry::get('logger')->debug("[PanelSupport] magic call with params: " . serialize($params));

			if (method_exists($this->_classname,$name)) {
				Zend_Registry::get('logger')->debug("[PanelSupport] Triggering '{$name}' in class '{$this->_classname}'");

				if (isset($params[0]) && isset($params[1])) {
					return $this->_class->$name($params[0], $params[1]);
				} elseif (isset($params[0])) {
					return $this->_class->$name($params[0]);
				} else {
					return $this->_class->$name();
				}
			}

			Zend_Registry::get('logger')->err("[PanelSupport] Command '{$name}' does not exist in class '{$this->_classname}'");

			return false;
		}
}
