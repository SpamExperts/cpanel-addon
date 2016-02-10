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
*/

/*
// Example code:
$api = new SpamFilter_ResellerAPI;
$api->authticket()->create(array(
    'username' => 'frank',
    'password' => 'qaz123',
    'validfor' => '3600', // seconds
));
*/

/**
 * SpamPanel API Wrapper (ResellerAPI)
 *
 * The API wrapper provides easy methods to integrate with the SpamExperts Webinterface API (also known as Reseller API)
 *
 * @class     SpamFilter_ResellerAPI
 * @category  SpamExperts
 * @package   ProSpamFilter
 * @author    $Author$
 * @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
 * @license   Closed Source
 * @version   2.5
 * @link      https://my.spamexperts.com/kb/34/Addons
 * @since     2.5
 */

/**
 * @method SpamFilter_ResellerAPI_Action domain()
 * @method SpamFilter_ResellerAPI_Action domainalias()
 * @method SpamFilter_ResellerAPI_Action domaincontact()
 * @method SpamFilter_ResellerAPI_Action user()
 * @method SpamFilter_ResellerAPI_Action version()
 * @method SpamFilter_ResellerAPI_Action authticket()
 */

class SpamFilter_ResellerAPI
{
    /**
     * Caller Magic
     *
     * This class is a stub which forwards it to the Action handler.
     *
     * @access public
     *
     * @param string $controller Controller to communicate with
     * @param string $params     This is a stub
     *
     * @return SpamFilter_ResellerAPI_Action
     */
    public function __call($controller, $params)
    {
        /** @var $logger SpamFilter_Logger */
        $logger = Zend_Registry::get('logger');
        $logger->debug("[ResellerAPI] Forwarded request for '{$controller}' module.");

        return new SpamFilter_ResellerAPI_Action($controller);
    }
}
