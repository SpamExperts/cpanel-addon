<?php

/*
 * Update .mo translation files from .po files
 */

require_once __DIR__ . '/../library/SpamFilter/Translation/Po2Mo.php';

$converter = new SpamFilter_Translation_Po2Mo;

$translationDirectory = __DIR__ . '/../translations/';
$compiledDirectory = $translationDirectory . 'compiled/';

if (is_dir($compiledDirectory) || mkdir($compiledDirectory, 0777, true)) {
    if ($handle = opendir($translationDirectory)) {
        //Get every file or sub directory in the defined directory
        while (($file = readdir($handle)) !== false) {
            $pathinfo = pathinfo($file);
            if (isset($pathinfo['extension']) && $pathinfo['extension'] == 'po') {
                $moFilePath = $compiledDirectory . $pathinfo['filename'] . '/LC_MESSAGES';
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
