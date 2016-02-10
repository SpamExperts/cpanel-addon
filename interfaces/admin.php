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
* @package   ProSpamFilter2
* @author    $Author$
* @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
* @license   Closed Source
* @version   3.0
* @link      https://my.spamexperts.com/kb/34/Addons
* @since     3.0
*/

define('ADDON_ROOT_FOLDER', realpath(dirname(__FILE__) . '/../'));

require_once ADDON_ROOT_FOLDER . '/application/bootstrap.php';

if (isset($_GET) && isset($_GET['q'])) {
	$_SERVER['REQUEST_URI'] = $_GET['q'];
}

// We also do MVC
Zend_Layout::startMvc(array(
    'layoutPath' => ADDON_ROOT_FOLDER . '/application/views/layouts',
    'layout' => 'default',
));

// Initialize the MVC component (actually only View+Controller)
//if (!empty($_ENV['cp_security_token'])) {
//    Zend_Controller_Front::getInstance()->setBaseUrl("{$_ENV['cp_security_token']}/cgi/addon_prospamfilter.cgi");
//}
Zend_Controller_Front::run(ADDON_ROOT_FOLDER . '/application/controllers');

require_once 'Zend/Controller/Action/HelperBroker.php';
Zend_Controller_Action_HelperBroker::addHelper(
    new Zend_Controller_Action_Helper_FlashMessenger());
