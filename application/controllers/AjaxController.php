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

                $data = (!empty($user))
				      ? $this->_panel->bulkProtect(array('domain' => $domain, 'type' => $type, 'owner_user' => $user, 'owner_domain' => $owner_domain))
				      : $this->_panel->bulkProtect(array('domain' => $domain, 'type' => $type, 'owner_domain' => $owner_domain));

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
                $domain = mb_strtolower($this->_getParam('domain'), 'UTF-8'); //@TODO: Add a filter to protect the input data (just to be sure)
                $type = strtolower($this->_getParam('type'));
                $owner_domain = mb_strtolower($this->_getParam('owner_domain'), 'UTF-8');
                $owner_user = $this->_getParam('owner_user');

                $config = Zend_Registry::get('general_config');

                $output = array();

                $this->_helper->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);

                $urlbase = ((false !== stristr($_SERVER['SCRIPT_NAME'], "index.raw")) ? '' : $_SERVER['SCRIPT_NAME']);

                /**
                 * Do not process with extra-domains in case of it is disabled in settings
                 * @see https://trac.spamexperts.com/ticket/17730
                 */
                if (!empty($owner_domain) && !(0 < $config->handle_extra_domains)) {
                    $output ['message'] = $this->t->_("Processing of addon- and parked domains is disabled in settings");
                    $output ['status'] = 'error';
                    exit(Zend_Json::encode($output));
                }

                if (!$this->_panel->validateOwnership($domain)) {
                    $output ['message'] = sprintf($this->t->_("You're not allowed to operate on the domain '%s'"), htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'));
                    $output ['status'] = 'error';
                    exit(Zend_Json::encode($output));
                }

                // Execute action
                $in_filter = $this->_panel->isInFilter($domain, $owner_domain);

                $hook = new SpamFilter_Hooks;

                /*
                 * if subdomains, parked and addon  types should be added ass aliases, then don't allow to add them individually
                 * @see https://trac.spamexperts.com/ticket/26075
                 */

                if (0 < $config->add_extra_alias && in_array($type, array('subdomain', 'parked', 'addon'))) {
                    $status['reason_status'] = "Skipped: Addon, parked and subdomains will be treated as an alias instead of a normal domain. Try to add/remove the domain";
                    $status['status'] = 'error';
                    $status['rawresult'] = SpamFilter_Hooks::SKIP_EXTRA_ALIAS;

                    $newstatus = $in_filter ? "unprotected": "protected";

                } else {
                    if (!$in_filter) {
                        // Add to filter
                        $status = $this->_panel->bulkProtect(array('domain' => $domain, 'type' => $type, 'owner_domain' => $owner_domain, 'owner_user' => (!empty($owner_user) ? $owner_user : $this->_panel->getDomainUser($domain))));
                        if (!empty($status['reason_status']) && 'ok' == $status['reason_status']) {
                            $status['status'] = true;
                        } else {
                            $status['status'] = 'error';
                        }
                        $newstatus = "protected";

                    } else {
                        // Remove from filter
                        if (('parked' == $type || 'addon' == $type || 'subdomain' == $type) && !empty($owner_domain) && $config->add_extra_alias) {
                            $status = $hook->DelAlias($owner_domain, $domain, true);
                            $newstatus = "unprotected";
                        } else {
                            // Try to find the domain's aliases
                            // @see https://trac.spamexperts.com/software/ticket/13043
                            $aliases = array();
                            if ($config->add_extra_alias) {
                                $domainOwnerUsername = $this->_panel->getDomainUser($domain);
                                $addonDomains = $this->_panel->getAddonDomains($domainOwnerUsername, $domain);
                                $parkedDomains = $this->_panel->getParkedDomains($domainOwnerUsername, $domain);
                                $subDomains = $this->_panel->getSubDomains($domainOwnerUsername, $domain);
                                $secondaryDomains = array();
                                if (is_array($addonDomains) && is_array($parkedDomains)) {
                                    $secondaryDomains = @array_merge_recursive($addonDomains, $parkedDomains, $subDomains);
                                } elseif (is_array($addonDomains)) {
                                    $secondaryDomains = $addonDomains;
                                } elseif (is_array($parkedDomains)) {
                                    $secondaryDomains = $parkedDomains;
                                } elseif (is_array($subDomains)) {
                                    $secondaryDomains = $subDomains;
                                }

                                foreach ($secondaryDomains as $data) {
                                    $aliases[] = $data['alias'];
                                }

                                $aliases = array_unique($aliases, SORT_REGULAR);

                                unset($secondaryDomains);
                            }

                            $status = $hook->DelDomain($domain, true, true, $aliases); // force removal, reset DNS zone for manual removes

                            $newstatus = "unprotected";
                        }
                    }
                }

                $idn = new IDNA_Convert;
                $domain = $idn->decode($domain);

                // Report back the status
                if (!isset($status['status'])) {
                    $status['status'] = false;
                    $status['rawresult'] = SpamFilter_Hooks::SKIP_APIFAIL;
                }

                if ($status['status'] !== true && isset($status['rawresult'])) {
                    switch ($status['rawresult']) {
                        case SpamFilter_Hooks::ALREADYEXISTS_NOT_OWNER:
                            $reason = $this->t->_(' you are not the owner of it.');
                            break;
                        case SpamFilter_Hooks::SKIP_EXTRA_ALIAS:
                            $reason = $this->t->_(' because subdomain, parked and addon domains will be treated as aliases.');
                            break;

                        case SpamFilter_Hooks::SKIP_REMOTE:
                            $reason = $this->t->_(' because domain uses remote exchanger.');
                            break;

                        case SpamFilter_Hooks::SKIP_DATAINVALID:
                            $reason = $this->t->_(' because data is invalid.');
                            break;

                        case SpamFilter_Hooks::SKIP_UNKNOWN:
                            $reason = $this->t->_(' because unknown error occurred.');
                            break;

                        case SpamFilter_Hooks::SKIP_APIFAIL:
                            $reason = $this->t->_(' because API communication failed.');
                            break;

                        case SpamFilter_Hooks::SKIP_ALREADYEXISTS:
                            $reason = $this->t->_(' because domain already exists.');
                            break;

                        case SpamFilter_Hooks::SKIP_INVALID:
                            $reason = $this->t->_(' because domain is not valid.');
                            break;

                        case SpamFilter_Hooks::SKIP_NOROOT:
                            $reason = $this->t->_(' because root domain cannot be added.');
                            break;

                        case SpamFilter_Hooks::API_REQUEST_FAILED:
                            $reason = $this->t->_(' because API request has failed.');
                            break;

                        case SpamFilter_Hooks::DOMAIN_EXISTS:
                            $reason = $this->t->_(' because domain already exists.');
                            break;

                        case SpamFilter_Hooks::ALIAS_EXISTS:
                            $reason = $this->t->_(' because alias already exists.');
                            break;

                        case SpamFilter_Hooks::DOMAIN_LIMIT_REACHED:
                            $reason = $this->t->_(' because domain limit reached.');
                            break;

                        default:
                            $reason = (!empty($status['additional'])) ? '. ' . ((is_array($status['additional'])) ? implode(', ', $status['additional']) : $status['additional']) : '';
                            break;
                    }
                    $output ['message'] = sprintf($this->t->_('The protection status of %s could not be changed to <strong>%s</strong>%s'), $domain, $newstatus, $reason);
                    $output ['status'] = 'error';
                } else {
                    $output ['message'] = sprintf($this->t->_('The protection status of %s has been changed to <strong>%s</strong>'), $domain, $newstatus);
                    $output ['status'] = 'success';
                }
                exit(Zend_Json::encode($output));
                break;
        }

        // Return content
        echo Zend_Json::encode($data);

        exit(0);
	}

    public function checkOwnerDomainNotSet($domain) {
        return !isset($domain['owner_domain']);
    }
}
