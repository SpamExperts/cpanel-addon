<?php

die;

// phpcs:ignore PHPCS_SecurityAudit.Misc.IncludeMismatch.ErrMiscIncludeMismatchNoExt,PHPCS_SecurityAudit.BadFunctions.EasyRFI.WarnEasyRFI,PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
require_once realpath(dirname(__FILE__) . '/../../') . '/application/bootstrap.php';

$apiAccessHash = file_get_contents('/root/.accesshash');

$api = Cpanel_PublicAPI::getInstance(array(
    'service' => array(
        'whm' => array(
            'config'    => array(
                'user' => 'root',
                'hash' => $apiAccessHash,
            ),
        ),
    ),
));

//$response = $api->whm_api('listaccts');
//
//if (!$response->validResponse()) {
//    foreach ($response->getResponseErrors() as $err) {
//        echo "$err\n";
//    }
//
//    die;
//}
//
//foreach ($response->acct as $acct) {
//    if (!empty($acct['domain'])) {
//        echo "{$acct['domain']}\n";
//    }
//}

$zones = array('org', 'com', 'net', 'ru', 'de', 'nl', 'cc', 'me', 'co.uk');
for ($i = 0; $i < 250; $i++) {
    // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.CryptoFunctions.WarnCryptoFunc
    $username = 'u' . substr(strtolower(md5(uniqid('u'))), 0, 7);
    $domain = "$username." . $zones[array_rand($zones)];

    $response = $api->whm_api('createacct', array(array(
        'username' => $username,
        'ip' => '0',
        'cpmod' => 'x3',
        'password' => 'qaz123',
        'contact email' => 'dmitry@spamexperts.com',
        'domain' => $domain,
        'useregns' => 0,
        'reseller' => 0,
    )));

    if (!$response->validResponse()) {
        foreach ($response->getResponseErrors() as $err) {
            // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.EasyXSS.EasyXSSwarn
            echo "$err\n";
        }
    }
}
