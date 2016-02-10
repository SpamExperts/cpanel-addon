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
class EmailController extends Zend_Controller_Action
{
    /** @var SpamFilter_Acl */
    protected $_acl;

    /** @var Zend_Translate_Adapter_Gettext */
    var $t;

	public function preDispatch()
	{
		// Setup ACL
		$this->_acl = new SpamFilter_Acl();

        // Get the translator
        $this->t = Zend_Registry::get('translator');

		// Feed the ACL system the current username
		$this->_acl->setRole( getenv('REMOTE_USER'), 'role_emailuser' ); //@TODO: Replace this with the actual userlevel, we need to determine this somehow.
		$this->view->acl = $this->_acl;
        $this->view->t = $this->t;
	}

	public function indexAction()
	{
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
        $this->view->headTitle()->append("Email Level");
		
		// @TODO: Remove me: Temp
		$this->_helper->viewRenderer->setNoRender(true);
		echo $this->t->_("This access level is not yet available.");
	}
}
