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
* @since     2.0
*/
class SpamFilter_DNS
{
    /**
     * ConfigureDNS
     * Configures the DNS
     *
     * @param string           $domain The Domain to configure
     * @param Cpanel_PublicAPI $apiRef The API reference (controlpanel) to use, or autoguess
     *
     * @return bool Status of DNS provisioning
     *
     * @access public
     * @static
     * @todo   Check if we are actually calling this and moving it to some more central place.
     */
    public static function ConfigureDNS($domain, $apiRef = null)
    {
        Zend_Registry::get('logger')->debug("[DNS] Configuring DNS for: '{$domain}'");

        $records = self::getFilteringClusterHostnames();

        /** @var $panel SpamFilter_PanelSupport_Cpanel */
        $panel = new SpamFilter_PanelSupport();

        if (!empty($apiRef)) {
            $panel->SetApi($apiRef);
        }
        $act = $panel->SetupDNS(array(
            'domain'  => $domain,
            'records' => $records
        ));

        Zend_Registry::get('logger')->debug("[DNS] Return values from panel DNS setup: " . serialize($act));

        return $act;
    }

    public static function DeconfigureDns($domain)
    {
        Zend_Registry::get('logger')->debug("[DNS] Deconfiguring DNS for: '{$domain}'");
        $records = array();

        // Revert MX records to $serverhostname
        $records['10'] = (string)SpamFilter_Core::GetServerName();

        // Actions to take are being executed by the Panel Library
        $panel = new SpamFilter_PanelSupport();
        /** @var SpamFilter_PanelSupport_Cpanel $panel */
        $act = $panel->SetupDNS(array(
            'domain'  => $domain,
            'records' => $records
        ));
        Zend_Registry::get('logger')->debug("[DNS] Return values from panel DNS setup: " . serialize($act));

        return $act;
    }   
    
    /**
     * Sets specified MX records for domain
     *
     * @access public
     * @param $domain - domain name
     * @param $routes - array of routes
     *
     * @return boolean
     */
    public static function RevertMXs($domain, $routes){
        if (empty($routes) || isset($routes['status'])) {
            Zend_Registry::get('logger')->debug("[DNS] Setting MX records failed. Gathered routes is not valid: " . serialize($routes));

            return false;
        }

        $routes = self::removePorts($routes);
        $routes = array_unique(self::reverseDNS($routes));

        if (self::validateRecords($routes)) {
            Zend_Registry::get('logger')->debug("[DNS] Setting MX records gathered from routes from domain: " . $domain);
            $panel = new SpamFilter_PanelSupport();
            $prior = 10;
            $records = array();
            foreach ($routes as $route) {
                $space = strpos($route, ' '); //for domains with special chars are routes are returned in format : 'domain route' - we want only route
                if ($space !== false) {
                    $route = substr($route, $space + 1);
                }
                $records[$prior] = $route;
                $prior += 10;
            }

            if (!empty($records)) {
                /** @var SpamFilter_PanelSupport_Cpanel $panel */
                $response = $panel->SetupDNS(array(
                        'domain' => $domain,
                        'records' => $records,
                        'unprotect' => true
                ));

                return $response;
            } else {
                return false;
            }
        } else {
            return self::DeconfigureDns($domain);
        }
    }

    /**
     * Returns hostnames of the filtering cluster (MX records for the domains under the filter)
     *
     * @access public
     *
     * @return array
     */
    public static function getFilteringClusterHostnames()
    {
        $records = array();

        if (Zend_Registry::isRegistered('general_config')) {
            $config = Zend_Registry::get('general_config');

            $records['10'] = $config->mx1;

            if (!empty($config->mx2)) {
                $records['20'] = $config->mx2;
            }

            if (!empty($config->mx3)) {
                $records['30'] = $config->mx3;
            }

            if (!empty($config->mx4)) {
                $records['40'] = $config->mx4;
            }
        }

        return $records;
    }

    /**
     * Checks if there's no IP addresses in routes
     * 
     * @param array $records
     *
     * @return boolean
     */
    private static function validateRecords($records)
    {
        foreach ($records as $record) {
            if (filter_var($record, FILTER_VALIDATE_IP) == $record) {
                return false;
            }            
        }

        return true;
    }
    /**
     * Removes ports from gathered routes
     * 
     * @param array $records
     *
     * @return array of sanitized routes
     */
    private static function removePorts($records)
    {
        $clearRoutes = [];

        foreach ($records as $route) {
            $x = explode('::', $route);
            if (count($x) > 1) {
                array_pop($x);
            }

            $clearRoutes[] = implode("::", $x);
        }

        return $clearRoutes;
    }

    /**
     * Try to get hostname from ip
     *
     * @param $records
     * @return mixed
     */
    private static function reverseDNS($records) {

        foreach($records as $key => $record){
            if(filter_var($record, FILTER_VALIDATE_IP) == $record){
                $hostname = gethostbyaddr($record);
                if ($hostname && $hostname != $record) {
                    $records[$key] = str_replace($record.".", "", $hostname);
                }
            }
        }

        return $records;
    }
}