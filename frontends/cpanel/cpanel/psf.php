#!/usr/local/cpanel/3rdparty/bin/php-cgi
<?php
/*
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering				*
*                                                               		*
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
*/
require_once('/usr/local/prospamfilter/application/bootstrap.php');

// NEW PART
if( isset($_GET) && isset($_GET['q']) )
{
	$_SERVER['REQUEST_URI'] = $_GET['q'];
}

// We also do MVC
Zend_Layout::startMvc(
	array(
	        'layoutPath' => BASE_PATH . '/application/views/layouts',
	        'layout' => 'default'
	)
);

// Initialize the MVC component (actually only View+Controller)
$front = Zend_Controller_Front::getInstance();
$front->setControllerDirectory( BASE_PATH . '/application/controllers' );
$front->setDefaultControllerName( 'domain' ); //@TODO: Possibly switch between email- and domain levels
$front->setDefaultAction( 'index' );	// @TODO: Evaluate
$front->setParam('useDefaultControllerAlways', true);
$front->dispatch();

// Append script and CSS so it looks fancy :-)
$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
if (null === $viewRenderer->view) {
    $viewRenderer->initView();
}
$view = $viewRenderer->view;
// phpcs:ignore PHPCS_SecurityAudit.BadFunctions.EasyXSS.EasyXSSwarn
echo $view->headStyle();
// phpcs:ignore PHPCS_SecurityAudit.BadFunctions.EasyXSS.EasyXSSwarn
echo $view->headScript();
exit();
?>
