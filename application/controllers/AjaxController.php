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
class AjaxController extends Zend_Controller_Action
{
    /** @var Zend_Translate_Adapter_Gettext */
    var $t;

    /**
     * Specific panel manager instance
     *
     * @access protected
     * @var SpamFilter_PanelSupport_Cpanel
     */
	protected $_panel;

	public function init()
		{
		// Disable renderer as we are returning raw data here.
		$this->_helper->viewRenderer->setNoRender(true);

		// Disable the template as well
		$this->_helper->layout()->disableLayout();

		// Setup the panel support
		$this->_panel = new SpamFilter_PanelSupport_Cpanel();

        // Get the translator
        $this->t = Zend_Registry::get('translator');
	}

	public function actionAction()
	{
		// Execute AJAX action. Which one depends on the given GET parameters.
		$data = array();

        /** @var $sessionManager stdClass */
        $sessionManager = new SpamFilter_Session_Namespace;

		switch (strtolower($this->_request->getParam('do'))) {
			case "checkstatus":
				$domain = $this->_request->getParam('domain');  //@TODO: Add filter
                $icon_ok = '<div class="ok-png"></div>';
				$icon_notok = '<div class="not-ok-png"></div>';

                $protected = $this->_panel->isInFilter($domain);

				$data['statusMsg'] = ((true === $protected)
                    ? '<font color="green">' . $icon_ok . $this->t->_(' This domain is present in the filter.') . '</font>'
                    : '<font color="red">' . $icon_notok . $this->t->_(' This domain is <strong>not</strong> present in the filter.') . '</font>');

                break;

			case "getcollectiondomains":
                defined('SKIP_DOMAIN_REMOTENESS_CHECK') or define('SKIP_DOMAIN_REMOTENESS_CHECK', 1);

                $user = SpamFilter_Core::getUsername();
                $level = ($this->_panel->getUserLevel($user) == 'role_enduser') ? 'user' : 'owner';
                $data = $this->_panel->getDomains(array('username' => $user, 'level' => $level));

                /**
                 * In some circumstances the array of domains should be resorted in a special way -
                 * add-on domains should follow their owner domains in case the "Add addon- and parked
                 * domains as an alias instead of a normal domain." option is activated
                 * @see https://trac.spamexperts.com/ticket/21659

                 * However if we treat addon domains as aliases then those should get removed
                 * https://trac.spamexperts.com/ticket/25024#comment:70
                 */

                $config = Zend_Registry::get('general_config');

                if (0 < $config->add_extra_alias && is_array($data)) {
                    // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.CallbackFunctions.WarnFringestuff
                    $input = array_filter($data, array($this, 'checkOwnerDomainNotSet'));
                    $data = array_values($input);
                }

                if (!$data) {
                    $sessionManager->bulkprotectinformer = null;
                    $sessionManager->bulkprotectinformerstatus = 'stop';
                }
                
                break;

			case "executebulkprotect":
				if ('protecting' == $sessionManager->bulkprotectinformerstatus) {
					$sessionManager->bulkprotectinformer = $this->t->_('The process of protecting is running...');
					$sessionManager->bulkprotectinformerstatus = 'stop';
				}

                $domain = $this->_request->getParam('domain');
				$type = $this->_request->getParam('type');
				$user = $this->_request->getParam('user');
				$owner_domain = $this->_request->getParam('owner_domain');

                /**
                 * Decode IDN domain
                 * @see https://trac.spamexperts.com/ticket/17688
                 */
                $idn = new IDNA_Convert;
                if (0 === stripos($domain, 'xn--')) {
                    $domain = $idn->decode($domain);
                }

                $manager = new SpamFilter_ProtectionManager();
                $data = $manager->protect($domain, $owner_domain, $type, $user);

                break;

			case "bulkprotecttimecomplete":

				// Save the last bulk protect time
				$settings = new SpamFilter_Configuration( CFG_PATH . DS . 'settings.conf' ); // <-- General settings
				$settings->updateOption('last_bulkprotect', time());

				$sessionManager->bulkprotectinformer = null;
				$sessionManager->bulkprotectinformerstatus = 'stop';

                break;

			case "bulkprotectinformer":
			    $data = array(
                    'text' => $sessionManager->bulkprotectinformer,
                    'status' => $sessionManager->bulkprotectinformerstatus,
                );

                break;

			case "bulkprotectinformerclear":
                $sessionManager->bulkprotectinformer = $sessionManager->bulkprotectinformerstatus = null;

                break;

            case "toggleprotection":
                $this->_helper->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);

                $domain = mb_strtolower($this->_getParam('domain'), 'UTF-8'); //@TODO: Add a filter to protect the input data (just to be sure)
                $type = strtolower($this->_getParam('type'));
                $owner_domain = mb_strtolower($this->_getParam('owner_domain'), 'UTF-8');
                $owner_user = $this->_getParam('owner_user');

                $toggler = new SpamFilter_ProtectionManager();
                $response = $toggler->toggleProtection($domain, $owner_domain, $type, $owner_user);

                // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.EasyXSS.EasyXSSwarn
                exit(Zend_Json::encode($response));
                break;
        }

        // Return content
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.EasyXSS.EasyXSSwarn
        echo Zend_Json::encode($data);

        exit(0);
	}

    public function checkOwnerDomainNotSet($domain) {
        return !isset($domain['owner_domain']);
    }
}
