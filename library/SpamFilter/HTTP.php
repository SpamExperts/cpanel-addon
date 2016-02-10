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

/** @noinspection PhpUndefinedClassInspection */
class SpamFilter_HTTP
{
    /**
     * getContent
     * Retrieve content using Zend_HTTP_Client
     *
     * @param string   $url      URL to retrieve
     * @param stdClass $config   Optional: Override configuration object with set Zend_Config_Ini object
     * @param string   $password Password to use to authenticate with.
     *
     * @param null     $rawData
     *
     * @return bool Status
     *
     * @access public
     * @static
     */
    final static public function getContent($url, $config = null, $password = null, $rawData = null)
    {
        /** @var SpamFilter_Logger $logger */
        /** @noinspection PhpUndefinedClassInspection */
        $logger = Zend_Registry::get('logger');

        try {
            /** @noinspection PhpUndefinedClassInspection */
            $obj_client = new Zend_Http_Client();

            /** @noinspection PhpUndefinedClassInspection */
            $addonversion = SpamFilter_Version::getUsedVersion();
            $useragent = "ProSpamFilter/{$addonversion}";
            try {

                /** @var SpamFilter_PanelSupport_Cpanel $panel */
                $panel = new SpamFilter_PanelSupport();
                $panelversion = $panel->getVersion();

                /** @noinspection PhpUndefinedClassInspection */
                $paneltype = ucfirst(strtolower(SpamFilter_Core::getPanelType()));

                if ((!empty($paneltype)) && (!empty($panelversion))) {
                    $useragent .= " ({$paneltype} {$panelversion})";
                }
            } catch (Exception $e) {}

            if (isset($useragent) && (!empty($useragent))) {
                $obj_client->setConfig(
                    array(
                         'useragent' => $useragent,
                         'strict'    => false,
                         'timeout'   => 90,
                    )
                );
            }

            /** @noinspection PhpUndefinedClassInspection */
            $adapter = new Zend_Http_Client_Adapter_Socket();
            $obj_client->setAdapter($adapter);
            $streamOpts = array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                )
            );
            $adapter->setStreamContext($streamOpts);
            $logger->debug("[HTTP] Going to request '{$url}'");
            try {
                $obj_client->setUri($url);
            } catch (Zend_Uri_Exception $e) {
                $logger->err("[HTTP] Invalid URL supplied");

                return false;
            }

            if (isset($config->apiuser) && (isset($config->apipass) || isset($password))) {
                if (!empty($password)) {
                    $obj_client->setAuth($config->apiuser, $password);
                } else {
                    $obj_client->setAuth($config->apiuser, $config->apipass);
                }
            }

            if (isset($rawData)) {
                // Rawdata is set, so we are going to POST
                $obj_client->setRawData($rawData, 'text/xml');

                if (isset($paneltype) && strtolower($paneltype) == 'plesk') {
                    $headers = array(
                        'HTTP_AUTH_LOGIN'   => $config->apiuser,
                        'HTTP_AUTH_PASSWD'  => $config->apipass,
                        'HTTP_PRETTY_PRINT' => true
                    );
                    $obj_client->setHeaders($headers);
                }

                $obj_client->request('POST');
            } else {
                $obj_client->request('GET');
            }

            $response = $obj_client->getLastResponse();
            $responsecode = $response->getStatus();

            if ($responsecode <> 200) {
                $logger->debug("[HTTP] Request failed with statuscode: {$responsecode}");

                return false;
            }

            $content = $response->getBody();

            return $content;
        } catch (Zend_Http_Client_Exception $e) {
            $logger->err('[HTTP] The HTTP request has failed, reason given:' . $e->getMessage());

            return false;
        }
    }

}
