<?php
/**
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering		*
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

class SpamFilter_Panel_Protect
{
    const COUNTS_OK 		= 'ok';
    const COUNTS_FAILED 	= 'failed';
    const COUNTS_NORMAL 	= 'normal';
    const COUNTS_PARKED 	= 'parked';
    const COUNTS_ADDON 		= 'addon';
    const COUNTS_SUBDOMAIN 	= 'subdomain';
    const COUNTS_SKIPPED 	= 'skipped';
    const COUNTS_UPDATED 	= 'updated';

    /**
     * Logger instance
     *
     * @access protected
     * @var SpamFilter_Logger
     */
    protected $_logger;

    /**
     * Config instance
     *
     * @access protected
     * @var Zend_Config
     */
    protected $_config;

    /**
     * Protect SpamFilter_Hooks object
     *
     * @access protected
     * @var SpamFilter_Hooks
     */
    protected $_hook;

    /**
	 *  Protected $_api object
	 *
	 * @access protected
	 */
	protected $_api;


    /**
     * Class constructor
     *
     * @access public
     * @param SpamFilter_Hooks $hook
     * @param $api
     * @return SpamFilter_Panel_Protect
     */
    public function __construct(SpamFilter_Hooks $hook = null, $api = null)
    {
        $this->_logger = Zend_Registry::get('logger');
        $this->_config	= Zend_Registry::get('general_config');
        $this->_hook = $hook;
        $this->_api = $api;

        $this->_result = self::initResult();
    }


	public static function initResult()
	{
		return array(
            'domain' => null,
            'counts' => array(
                self::COUNTS_OK      => 0,
                self::COUNTS_FAILED  => 0,
                self::COUNTS_NORMAL  => 0,
                self::COUNTS_PARKED  => 0,
                self::COUNTS_ADDON   => 0,
                self::COUNTS_SUBDOMAIN   => 0,
                self::COUNTS_SKIPPED => 0,
                self::COUNTS_UPDATED => 0,
            ),
            'reason' => null,
            'reason_status' => null,
            'time_start' => time(),
            'time_execute' => null,
        );
	}

	public function countsUp($item)
	{
		// Save it in the final result array
		if ($item) {
			$this->_result['counts'][$item]++;
		}
	}

	public function addDomainReason($domain, $reason)
	{
		// Save into the final result array
		switch ($reason) {
			case SpamFilter_Hooks::SKIP_DATAINVALID:
				$this->_result['reason'] = "Skipped: Data is invalid";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::SKIP_DATAINVALID;
				break;

			case SpamFilter_Hooks::SKIP_UNKNOWN:
				$this->_result['reason'] = "Skipped: Unknown error";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::SKIP_UNKNOWN;
				break;

			case SpamFilter_Hooks::SKIP_REMOTE:
				$this->_result['reason'] = "Skipped: Domain is remote";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::SKIP_REMOTE;
				break;

			case SpamFilter_Hooks::SKIP_APIFAIL:
				$this->_result['reason'] = "Skipped: API communication failed";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::SKIP_APIFAIL;
				break;

			case SpamFilter_Hooks::SKIP_ALREADYEXISTS:
				$this->_result['reason'] = "Skipped: Domain already exists";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::SKIP_ALREADYEXISTS;
				break;

			case SpamFilter_Hooks::SKIP_INVALID:
				$this->_result['reason'] = "Skipped: Domain is not valid";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::SKIP_INVALID;
				break;

			case SpamFilter_Hooks::SKIP_NOROOT:
				$this->_result['reason'] = "Skipped: Root domain cannot be added";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::SKIP_NOROOT;
				break;

			case SpamFilter_Hooks::ALREADYEXISTS_ROUTESET:
				$this->_result['reason'] = "Route & MX have been updated";
				$this->_result['reason_status'] = "ok";
                                $this->_result['rawresult'] = SpamFilter_Hooks::ALREADYEXISTS_ROUTESET;
				break;

			case SpamFilter_Hooks::ALREADYEXISTS_ROUTESETFAIL:
				$this->_result['reason'] = "Route & MX change has failed";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::ALREADYEXISTS_ROUTESETFAIL;
				break;

			case SpamFilter_Hooks::API_REQUEST_FAILED:
				$this->_result['reason'] = "API request has failed";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::API_REQUEST_FAILED;
				break;

			case SpamFilter_Hooks::DOMAIN_EXISTS:
				$this->_result['reason'] = "Domain already exists";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::DOMAIN_EXISTS;
				break;

			case SpamFilter_Hooks::ALREADYEXISTS_NOT_OWNER:
				$this->_result['reason'] = "Domain already exists (you are not the owner)";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::ALREADYEXISTS_NOT_OWNER;
				break;

			case SpamFilter_Hooks::ALIAS_EXISTS:
				$this->_result['reason'] = "Alias already exists";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::ALIAS_EXISTS;
				break;

		        case SpamFilter_Hooks::DOMAIN_LIMIT_REACHED:
				$this->_result['reason'] = "Domain not added: Domain Limit reached";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::DOMAIN_LIMIT_REACHED;
				break;

			case SpamFilter_Hooks::DOMAIN_ADDED:
				$this->_result['reason'] = "Domain has been added";
				$this->_result['reason_status'] = "ok";
                                $this->_result['rawresult'] = SpamFilter_Hooks::DOMAIN_ADDED;                               
				break;

			case SpamFilter_Hooks::NO_SUCH_DOMAIN:
				$this->_result['reason'] = "Skipped: No such domain";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::NO_SUCH_DOMAIN;
				break;

			case SpamFilter_Hooks::API_USER_INACTIVE:
				$this->_result['reason'] = "Skipped: API user is inactive";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::API_USER_INACTIVE;
				break;

                        case SpamFilter_Hooks::WRONG_DESTINATION_GIVEN:
				$this->_result['reason'] = "Skipped: Wrong destination given";
				$this->_result['reason_status'] = "error";
                                $this->_result['rawresult'] = SpamFilter_Hooks::WRONG_DESTINATION_GIVEN;
				break;

			default:
			    $this->_result['reason'] = $reason;
				$this->_result['reason_status'] = "error";
				break;
		}
	}

	public function getResult()
	{
		$this->_result['time_execute'] = time() - $this->_result['time_start'];
		return $this->_result;
	}

	public function setDomain($domain)
	{
		$this->_result['domain'] = $domain;
	}
}
