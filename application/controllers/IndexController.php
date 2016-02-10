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
class IndexController extends Zend_Controller_Action
{
    /** @var SpamFilter_Acl */
    protected $_acl;

    /** @var SpamFilter_Controller_Action_Helper_FlashMessenger */
    var $_flashMessenger;

    /** @var Zend_Translate_Adapter_Gettext */
    var $t;

    protected $_paneltype;

    protected $_config;

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
                    Zend_Session::setOptions(array("strict" => false));
                    $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
                    Zend_Session::setOptions(array("strict" => true));
                }
            }
        }

        // Get the translator
        $this->t = Zend_Registry::get('translator');

        if (SpamFilter_Version::updateAvailable()) {
            // Show update avail.
            $version       = trim(SpamFilter_Version::getCurrentVersion()); // Do not run realtime
            $upgradeString = sprintf($this->t->_('There is an upgrade available to %s'), $version) . '. <strong><a href="?q=admin/update">' . $this->t->_('Upgrade now') . '</a></strong>';
            $this->_flashMessenger->addMessage(array('message' => $upgradeString, 'status' => 'info'));
        }

        $this->_paneltype = strtolower( SpamFilter_Core::getPanelType() );
    }

    public function preDispatch()
    {
        // Setup ACL
        $this->_acl = new SpamFilter_Acl();

        $username = SpamFilter_Core::getUsername();

        // Retrieve usertype using the Panel driver
        $panel     = new SpamFilter_PanelSupport();
        $userlevel = $panel->getUserLevel();

        /**
         * Get changed brandname (in case of it was set)
         * @see https://trac.spamexperts.com/ticket/16804
         */
        $branding   = new SpamFilter_Brand();
        $brandname  = $branding->getBrandUsed();
        if (!$brandname) {
            $brandname = 'Professional Spam Filter';
        }

        // Feed the ACL system the current username
        $this->_acl->setRole($username, $userlevel);

        $this->view->headTitle()->set($brandname);
        $this->view->headTitle()->setSeparator(' | ');
        if(strpos($_SERVER['PHP_SELF'], '/paper_lantern/') === false){
            $this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap.min.css') );
        }
	$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap-responsive.min.css') );
	$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'addon.css') );
	$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'jquery.min.js') );
	$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'bootstrap.min.js') );

        $this->view->acl = $this->_acl;
        $this->view->t = $this->t;
        $this->view->brandname = $brandname;
    }

    public function indexAction()
    {

        $this->view->headTitle()->append("Dashboard");

        $this->view->image_folder = 'psf';

        if ((!$this->_acl->isAllowed('settings_admin')) && (!$this->_acl->isAllowed('settings_reseller'))) {
            $this->_flashMessenger->addMessage(
                array('message' => $this->t->_('Your access level is not high enough to use this addon.') . ' <br/> ' . $this->t->_('Please contact your provider.'),
                      'status'  => 'error')
            );
            $this->_helper->viewRenderer->setNoRender(); // Do not render the page
        }

        // Check if the addon is configured.
        $config = Zend_Registry::get('general_config');

        if (empty($config->apiuser)) {
            $this->_flashMessenger->addMessage(
                array('message' => $this->t->_('The addon appears to be not configured. You need to do this before you can use it.') . ' <strong><a href="?q=admin/settings">' . $this->t->_('Configure addon') . '</a></strong>',
                      'status'  => 'warning')
            );
        }

        // Check if resellers have no access to the addon.
        if (isset($config->disable_reseller_access) && (1 == $config->disable_reseller_access)) {
            $panel = new SpamFilter_PanelSupport();
            if ('role_reseller' == $panel->getUserLevel()) {
                $this->_flashMessenger->addMessage(
                    array('message' => $this->t->_('Resellers have no permissions to use this addon.') . ' <br/> ' . $this->t->_('Please contact your provider.'),
                          'status'  => 'error')
                );
                $this->_helper->viewRenderer->setNoRender(); // Do not render the page
                return false;
            }
        }

        // We render a different file than $action.phtml, disable the renderer so it does not attempt to do so.
        $this->getHelper('viewRenderer')->setNoRender();

        $this->render();
    }
}
