<?php

/*
 * Update .mo translation files from .po files
 */

// phpcs:ignore PHPCS_SecurityAudit.BadFunctions.EasyRFI.WarnEasyRFI
require_once __DIR__ . '/../library/SpamFilter/Translation/Po2Mo.php';

$converter = new SpamFilter_Translation_Po2Mo;

$translationDirectory = __DIR__ . '/../translations/';
$compiledDirectory = $translationDirectory . 'compiled/';

// phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
if (is_dir($compiledDirectory) || mkdir($compiledDirectory, 0777, true)) {
    // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
    if ($handle = opendir($translationDirectory)) {
        //Get every file or sub directory in the defined directory
        while (($file = readdir($handle)) !== false) {
            // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
            $pathinfo = pathinfo($file);
            if (isset($pathinfo['extension']) && $pathinfo['extension'] == 'po') {
                $moFilePath = $compiledDirectory . $pathinfo['filename'] . '/LC_MESSAGES';
                // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.FilesystemFunctions.WarnFilesystem
                if (is_dir($moFilePath) || mkdir($moFilePath, 0777, true)) {
                    $converter->convert($translationDirectory . $file, $moFilePath . '/se.mo');
                }
            }
        }
        closedir($handle);
    }
} else {
    echo "Couldn't access or create directory '../translations/compiled'";
}
