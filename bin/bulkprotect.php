#!/usr/local/cpanel/3rdparty/bin/php
<?php

if ('cli' != PHP_SAPI) {
    die("This script works in CLI mode only");
}

$processUser = posix_getpwuid(posix_geteuid());
$processUsername = !empty($processUser['name']) ? $processUser['name'] : '';
if ('root' != $processUsername) {
    die("This script can be executed by the root user only");
}

require_once dirname(__FILE__) . '/../application/bootstrap.php';

set_time_limit(0);
ignore_user_abort(0);
error_reporting(0);

/** @var SpamFilter_Logger $logger */
$logger = Zend_Registry::get('logger');
$stdoutWriter = new Zend_Log_Writer_Stream('php://output');
$stdoutWriter->addFilter(new Zend_Log_Filter_Priority(Zend_Log::INFO));
$logger->addWriter($stdoutWriter);

defined('SKIP_DOMAIN_REMOTENESS_CHECK') or define('SKIP_DOMAIN_REMOTENESS_CHECK', 1);

$panel = new SpamFilter_PanelSupport_Cpanel;
$data = $panel->getDomains(array('username' => $processUsername, 'level' => 'owner'));

$config = Zend_Registry::get('general_config');
$protectionManager = new SpamFilter_ProtectionManager();

/**
 * In some circumstances the array of domains should be resorted in a special way -
 * add-on domains should follow their owner domains in case the "Add addon- and parked
 * domains as an alias instead of a normal domain." option is activated
 * @see https://trac.spamexperts.com/ticket/21659
 */
if (0 < $config->add_extra_alias && is_array($data)) {
    $resortedData = $secondaryDomains = array();
    foreach ($data as $dom) {
        if (!empty($dom['owner_domain'])) {
            if (!isset($secondaryDomains[$dom['owner_domain']])) {
                $secondaryDomains[$dom['owner_domain']] = array();
            }
            $secondaryDomains[$dom['owner_domain']][] = $dom;
        }
    }
    foreach ($data as $dom) {
        if (!isset($dom['owner_domain'])) {
            $resortedData[] = $dom;
            if (!empty($secondaryDomains[$dom['name']])) {
                foreach ($secondaryDomains[$dom['name']] as $secDom) {
                    $resortedData[] = $secDom;
                }

            }
        }
    }
    unset($secondaryDomains);
    $data = $resortedData;
}

if (!empty($data) && is_array($data)) {
    $idn = new IDNA_Convert;
    $progress = 0; $tatalDomains = count($data);
    foreach ($data as $domainDescriptor) {
        $domain = !empty($domainDescriptor['domain']) ? $domainDescriptor['domain'] : '';
        $type = !empty($domainDescriptor['type']) ? $domainDescriptor['type'] : '';
        $user = !empty($domainDescriptor['user']) ? $domainDescriptor['user'] : '';
        $owner_domain = !empty($domainDescriptor['owner_domain']) ? $domainDescriptor['owner_domain'] : '';

        if (0 === stripos($domain, 'xn--')) {
            $domain = $idn->decode($domain);
        }

        $protectResponse = $protectionManager->protect($domain, $owner_domain, $type, $user);

        $progress++;

        $statusMessage = 1 < $progress
            ? "[STATUS UPDATE] {$progress} domains out of {$tatalDomains} have been processed"
            : "[STATUS UPDATE] {$progress} domain out of {$tatalDomains} has been processed";
        $logger->info($statusMessage);
    }
}
