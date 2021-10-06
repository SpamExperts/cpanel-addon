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
* @since     2.5
*/
/** Zend_Controller_Action */
class BulkprotectController extends Zend_Controller_Action
{
    /** @var SpamFilter_Acl */
    protected $_acl;

    /** @var SpamFilter_Controller_Action_Helper_FlashMessenger */
    var $_flashMessenger;

    /** @var Zend_Translate_Adapter_Gettext */
    var $t;

    protected $_hasAPIAccess;

    /**
     * Specific panel manager instance
     *
     * @access protected
     * @var SpamFilter_PanelSupport_Cpanel
     */
	protected $_panel;

	public function init()
	{
		try {
			// Enable the flash messenger helper so we can send messages.
			$this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
		} catch (Zend_Session_Exception $e) {
			if (!$this->_helper->hasHelper('FlashMessenger')) {
				if (!Zend_Session::isStarted() && Zend_Session::sessionExists()) {
					Zend_Controller_Action_HelperBroker::addHelper(
						new SpamFilter_Controller_Action_Helper_FlashMessenger()
					);
					$this->_flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
				} else {
					Zend_Session::setOptions(array ("strict" => false));
					$this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
					Zend_Session::setOptions(array ("strict" => true));
				}
			}
		}

		$this->_panel = new SpamFilter_PanelSupport( );

		// Set the output buffering to 0.
		ini_set('output_buffering', "0");
	}

	public function preDispatch()
	{
		// Setup ACL
		$this->_acl = new SpamFilter_Acl();

		$username = SpamFilter_Core::getUsername();

		// Retrieve usertype using the Panel driver
		$userlevel = $this->_panel->getUserLevel();

		// Feed the ACL system the current username
		$this->_acl->setRole( $username, $userlevel );

        // Get the translator
        $this->t = Zend_Registry::get('translator');

        /**
         * Get changed brandname (in case of it was set)
         * @see https://trac.spamexperts.com/ticket/16804
         */
        $branding   = new SpamFilter_Brand();
        $brandname  = $branding->getBrandUsed();
        if (!$brandname){
            $brandname = 'Professional Spam Filter';
        }

        $this->view->headTitle()->set($brandname);
		$this->view->headTitle()->setSeparator(' | ');
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap.min.css') );
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap-responsive.min.css') );
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'addon.css') );
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'jquery.min.js') );
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'bootstrap.min.js') );
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'punycode.js') );

		$this->view->acl = $this->_acl;
        $this->view->t = $this->t;
        $this->view->hasAPIAccess = $this->_hasAPIAccess = $branding->hasAPIAccess();
    }

	public function indexAction()
	{
        if (!$this->_hasAPIAccess) { return;}

        $this->view->headTitle()->append("Bulk Protect");
        if( !$this->_acl->isAllowed('bulkprotect') )
        {
            $this->_flashMessenger->addMessage( array('message' => $this->t->_('You do not have permission to this part of the system.'), 'status' => 'error') );
            $this->_helper->viewRenderer->setNoRender(); // Do not render the page
            return false;
        }

        // Check if configured
        $config = Zend_Registry::get('general_config');
        $this->view->isConfigured = (!empty($config->apiuser)) ? true : false;
        if(!$this->view->isConfigured) { return false; }

        if((!isset($config->last_bulkprotect)) || (empty($config->last_bulkprotect)))
        {
            $this->_flashMessenger->addMessage( array('message' => $this->t->_('You have not executed a bulkprotect yet.<br/>This is recommended to protect all domains on this server.'), 'status' => 'info') );
            $this->view->lastBulkprotect = "never";
        } else {
            $this->view->lastBulkprotect = date('d F Y H:i', trim($config->last_bulkprotect) );
        }


        $form = new SpamFilter_Forms_Bulkprotect();

        $this->view->bulkProtect = $form;
    }
}
