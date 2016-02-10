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
class SpamFilter_Version
{

    /**
     * getCurrentVersion
     * Retrieve the current version available
     *
     * @param string $tier
     * @param bool   $realtime Whether it shoudl be realtime or cached
     *
     * @return string|bool Version|Statuscode
     *
     * @todo   Update this to use a dynamic pagecall.
     *
     * @access public
     * @static
     */
    public static function getCurrentVersion($tier = "stable", $realtime = false)
    {
        $dd = 0;
        $time = time();
        /** @noinspection PhpUndefinedClassInspection */
        $config = Zend_Registry::get(SpamFilter_Configuration::ID_IN_REGISTRY);
        /** @var SpamFilter_Logger $logger */
        /** @noinspection PhpUndefinedClassInspection */
        $logger = Zend_Registry::get('logger');

        if ($realtime == false) {
            $logger->debug("[Version] - NON-REALTIME CHECK");
            $t = $config->lastupdatecheck;
            /** @noinspection PhpUndefinedClassInspection */
            $settings = new SpamFilter_Configuration(CFG_PATH . DS . 'settings.conf'); // <-- General settings

            if (!empty($t)) {
                $logger
                    ->debug("[Version] - DateDiff: Checking difference between '{$time}' and '{$t}'");
                /** @noinspection PhpUndefinedClassInspection */
                $dd = SpamFilter_Core::datediff('h', $t, $time, true);
                $logger->debug("[Version] - DateDiff: Non Realtime. Difference = '{$dd}' hours");
            } else {
                $logger->debug("[Version] - DateDiff: Current saved timestamp is empty");
                $yesterday = strtotime("Yesterday");
                $settings->updateOption('lastupdatecheck', $yesterday); // Write it
            }
        } else {
            $logger->debug("[Version] - REALTIME CHECK");
        }

        if ($dd > 6 || $realtime) {
            $logger->debug("[VersionCheck] Version is older > 6h or realtime");

            $version = trim(SpamFilter_Version::getUsedVersion());
            /** @noinspection PhpUndefinedClassInspection */
            $paneltype = strtolower(SpamFilter_Core::getPanelType());
            $tier = strtolower($tier);
            /** @noinspection PhpUndefinedClassInspection */
            if (SpamFilter_Core::isTesting()) {
                $tier = "testing";
            }
            $url = "http://download.seinternal.com/integration/?act=getUpdate&" .
                   "panel={$paneltype}&tier={$tier}&curver={$version}";

            /** @noinspection PhpUndefinedClassInspection */
            $json_data = SpamFilter_HTTP::getContent($url);
            /** @noinspection PhpUndefinedClassInspection */
            $info = Zend_Json::decode($json_data);
            if ($info['status'] && (isset($info['data']['version']) && (!empty($info['data']['version'])))) {
                $logger->debug("[Version] Response is not null & version = not empty. ");

                if ((isset($settings)) && (is_object($settings))) {
                    $logger->debug("[Version] Caching time & version");

                    // Write current time to ini file.
                    $settings->updateOption('lastupdatecheck', $time);

                    // Write the current version to the ini file.
                    $settings->updateOption('curversion', $info['data']['version']);
                }
                $logger->debug("[Version] Returning Version = '{$info['data']['version']}'");

                return $info['data']['version'];
            } else {
                $logger->debug("[Version] CurVersion is empty");
            }
        } else {
            return trim($config->curversion);
        }

        return false;
    }

    /**
     * getUsedVersion
     * Returns the current used version
     *
     *
     * @return string Version
     *
     * @access public
     * @static
     */
    public static function getUsedVersion()
    {
        return trim(file_get_contents(BASE_PATH . DS . "application" . DS . "version.txt"));
    }

    /**
     * updateAvailable
     * Checks whether the available version exceeds the current one
     *
     * @param string $tier
     * @param bool   $force Force checking (override realtime check)
     *
     * @return bool Status whether there is an update available
     *
     * @access public
     * @static
     * @see    getCurrentVersion()
     * @see    getUsedVersion()
     */
    public static function updateAvailable($tier = "stable", $force = false)
    {
        if (empty($tier)) {
            $tier = 'stable';
        }

        $new = self::getCurrentVersion($tier, $force);

        /** @var SpamFilter_Logger $logger */
        /** @noinspection PhpUndefinedClassInspection */
        $logger = Zend_Registry::get('logger');
        if (!empty($new)) {
            $logger->debug("[Version] New = {$new}");
            $cur = self::getUsedVersion();

            if (empty($cur)) {
                $cur = '0.0.0';
            }

            $vc = version_compare($cur, $new);
            $logger->debug("[Version] Comparing '{$cur}' with '{$new}' --> '{$vc}'");
            if ($vc == -1) {
                $logger->debug("[Version] Update available '{$new}' (was: '{$cur}')");

                return true;
            }
            $logger->debug("[Version] No update available '{$new}' (is: '{$cur}')");
        } else {
            $logger->debug("[Version] Update check failed because of missing remote versiondata");
        }

        return false;
    }
}
