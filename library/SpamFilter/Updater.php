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
 * @since     2.0
 */
class SpamFilter_Updater
{
    /**
     * autoUpdateEnabled
     * Checks whether auto update is enabled
     *
     *
     * @return bool Whether it is enabled or disabled
     *
     * @access public
     * @static
     */
    public static function autoUpdateEnabled()
    {
        /** @noinspection PhpUndefinedClassInspection */
        if (!Zend_Registry::isRegistered(SpamFilter_Configuration::ID_IN_REGISTRY)) {
            /** @noinspection PhpUndefinedClassInspection */
            $conf = new SpamFilter_Configuration(CFG_PATH . DS . 'settings.conf');
            unset($conf);
        }

        /** @noinspection PhpUndefinedClassInspection */
        $config = Zend_Registry::get(SpamFilter_Configuration::ID_IN_REGISTRY);

        return (isset($config->auto_update) && 0 < $config->auto_update);
    }

    /**
     * update
     * Execute required update commands to update to the latest version
     *
     * @param string $tier
     * @param bool   $forced
     * @param bool   $viaFrontend
     *
     * @throws RuntimeException
     * @return string|bool status Whether the installation was completed
     *
     * @todo   Rewrite this system to use new structure (JSON / XML request)
     *
     * @access public
     * @static
     */
    public static function update($tier = "stable", $forced = false, $viaFrontend = false)
    {
        /** @var $logger SpamFilter_Logger */
        $logger = Zend_Registry::get('logger');

        if (!function_exists('system')) {
            throw new RuntimeException("The 'system' function is disabled. Unable to continue the update process.");
        }

        $version   = trim(SpamFilter_Version::getUsedVersion());
        $paneltype = strtolower(SpamFilter_Core::getPanelType());
        $tier      = strtolower($tier);

        $logger->debug("[Update] Paneltype is {$paneltype}");

        $path = "/usr/src/prospamfilter/";
        if (SpamFilter_Core::isTesting()) {
            $tier = "testing";
        }

        $logger->info("[Update] Going to update the {$paneltype} addon to v{$version} in tier {$tier}");
        //  Windows way to update addon
        if (SpamFilter_Core::isWindows()) { 
            $tier = ($tier <> "stable") ? ' trunk' : '';
            $cmd = '"' . BASE_PATH . DS . 'bin' . DS . 'installer' . DS . 'installer.bat" ' . $tier;  
            $pid = shell_exec($cmd);
        } else {

            if (in_array($paneltype, array('plesk')) && $viaFrontend) {
                $logger->info("[Update] Triggering alternative update system.");

                return SpamFilter_Updater::alternativeUpdate($tier, $forced, $viaFrontend);
            }

            /**
             * The update log file (/tmp/psf_update.log) should not exist
             * @see https://trac.spamexperts.com/ticket/20771
             */
            $updateLogFilePrefix = 'psf_update';
            foreach (scandir('/tmp') as $eachTmpFile) {
                if (preg_match("~^{$updateLogFilePrefix}.*\\.log\$~i", $eachTmpFile)) {
                    unlink('/tmp/'.$eachTmpFile);
                }
            }
            $updateLogFile = '/tmp/' . uniqid($updateLogFilePrefix) . '.log';
            $cmd = 'nohup nice -n 10 /usr/local/prospamfilter/bin/installer/installer.sh'
                . (($tier <> "stable") ? ' trunk' : '') . ' > ' . $updateLogFile . ' 2>&1 & echo $!';
            $pid = shell_exec($cmd);
        }

        $logger->info("[Update] The installer.sh has been executed with the PID=$pid");

        return true;
    }

    public static function alternativeUpdate($tier = "stable", $forced = false, $viaFrontend = false)
    {
        // use our alternative update mechanism in limited environments
        $command = sprintf(
            "%s --updater %s %s %s 2>&1", SpamFilter_Core::getConfigBinary(), (($forced) ? '--force' : '--noforce'), $tier, (($viaFrontend) ? 'true' : 'false')
        );

        /** @var $logger SpamFilter_Logger */
        $logger = Zend_Registry::get('logger');

        $logger->debug("[Update] Alternative update executing: '{$command}'.");

        $fullOut = '';
        $retCode = 0;
        exec($command, $fullOut, $retCode);

        $logger->debug("[Update] Alternative update exited with exit code $retCode. Output is: " . join(' ', $fullOut));

        if ($retCode == 0) {
            $logger->info("[Update] Alternative update completed!.");

            return true;
        }

        $logger->err("[Update] Alternative update failed with exit code " . $retCode);

        return false;
    }
}
