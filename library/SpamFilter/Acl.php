<?php
/*
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
* @since     3.0
*/

class SpamFilter_Acl
{
	protected $_acl;

	/**
	 * __construct
	 * Initialize ACL system
	 *
	 *
	 * @return void
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->_acl = new Zend_Acl();

		// Emailuser: email level login
		$this->_acl->addRole(new Zend_Acl_Role('role_emailuser'));

		// Enduser: Customer (domain level login)
		$this->_acl->addRole(new Zend_Acl_Role('role_enduser'));

		// Serviceuser: Service user (service user login)
		$this->_acl->addRole(new Zend_Acl_Role('role_serviceuser'));

		// Client: Customer (domain level login)
		$this->_acl->addRole(new Zend_Acl_Role('role_client'));

		// Reseller: Reseller level login (access to all domains assigned to account)
		$this->_acl->addRole(new Zend_Acl_Role('role_reseller'));

		// Admin: Full access to system including main config
		$this->_acl->addRole(new Zend_Acl_Role('role_admin'));

		// Setting inheritance, so that higher-level users may do lower-level actions as well.
		$this->_acl->add(new Zend_Acl_Resource('support')); // Admin can request support (?)
		$this->_acl->add(new Zend_Acl_Resource('update')); // Admin can update addon manually
		$this->_acl->add(new Zend_Acl_Resource('settings_admin')); // Admin can configure the main addon settings
		$this->_acl->add(new Zend_Acl_Resource('settings_branding')); // Admin can configure branding
		$this->_acl->add(new Zend_Acl_Resource('settings_reseller')); // Reseller can change their own settings
		$this->_acl->add(new Zend_Acl_Resource('migration')); // We should be able to bulkprotect

		$this->_acl->add(new Zend_Acl_Resource('list_resellers')); // We should be able to list resellers
		$this->_acl->add(new Zend_Acl_Resource('loginas_reseller')); // We should be to login as a reseller
		$this->_acl->add(new Zend_Acl_Resource('list_domains')); // We should be able to list domains
		$this->_acl->add(new Zend_Acl_Resource('list_accounts')); // We should be able to list accounts
		$this->_acl->add(new Zend_Acl_Resource('bulkprotect')); // We should be able to bulkprotect

		// Resellers may not work with admin settings, branding settings or update config
		$this->_acl->deny('role_reseller', array('settings_admin', 'settings_branding', 'list_resellers', 'loginas_reseller', 'update', 'support', 'migration') );

		// Resellers may access their own reseller settings
		$this->_acl->allow('role_reseller', array('settings_reseller', 'list_domains', 'list_accounts') );

		// Admin is allowed to do anything...
		$this->_acl->allow('role_admin');

		// Except reseller settings, because they do not use it.
		$this->_acl->deny('role_admin', 'settings_reseller');

		// Disallow account listing for admin/reseller until #11979 is to be implemented.
		$this->_acl->deny('role_admin', 'list_accounts');
		$this->_acl->deny('role_reseller', 'list_accounts');

		$pt = SpamFilter_Core::getPanelType();
		return $this->_acl;
	}

	/**
	 * getAcl
	 * Returns the ACL object
	 *
	 *
	 * @return object Zend_ACL
	 *
	 * @access public
	 */
	public function getAcl()
	{
		return $this->_acl;
	}

	/**
	 * setRole
	 * Provides a user a certain role-set
	 *
         * @param string $user Username
         * @param array $roles Array of roles for user
	 *
	 * @return boolean status
	 *
	 * @access public
	 */
	public function setRole($user, $roles)
	{
        /** @noinspection PhpUndefinedClassInspection */
        Zend_Registry::get('logger')->debug("[ACL] Setting role for '{$user}' to '{$roles}'");
		return $this->_acl->addRole(new Zend_Acl_Role($user), $roles);
	}

	/**
	 * getRole
	 * Returns the role-set for a user
	 *
         * @param $user Username
	 *
	 * @return object Something
	 *
	 * @access public
	 */
	public function getRole($user)
	{
		Zend_Registry::get('logger')->debug("[ACL] Retrieve role for '{$user}'");
	}

	/**
	 * isAllowed
	 * Checks whether a user may execute a resource
	 *
         * @param $user Username
         * @param $resource Resource to check
	 *
	 * @return boolean status
	 *
	 * @access public
	 */
	public function isAllowed( $resource, $user = '' )
	{
		if (empty($user))
		{
			// Obtain the username, since it is not provided to us.
			$user = SpamFilter_Core::getUsername();
		}
		Zend_Registry::get('logger')->debug("[ACL] Checking if '{$user}' has access to resource '{$resource}'");
		return $this->_acl->isAllowed( $user, $resource) ? true : false;
	}
}
