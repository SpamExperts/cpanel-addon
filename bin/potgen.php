<?php

define('DOCUMENT_ROOT', realpath(dirname(__FILE__) . '/../'));

set_time_limit(0);
clearstatcache();

$files = rglob(DOCUMENT_ROOT, '{*.phtml,*.php}', GLOB_BRACE);

$filesToParse = array();
foreach ($files as $filename) {
    if (strpos($filename, '/library/Zend/' /* Exclude Zend */) === false && is_readable($filename)) {
        $filesToParse[] = $filename;
    }
}

$listOfFiles = tempnam(realpath(sys_get_temp_dir()), 'sources_for_translation_');
file_put_contents($listOfFiles, join("\n", $filesToParse));

$potContents = `xgettext --files-from=$listOfFiles -o /dev/stdout -L PHP --from-code=UTF-8`;

/**
 * @see https://trac.spamexperts.com/ticket/21186#comment:17
 */
$potContents = str_replace('#: ' . realpath(__DIR__ . '/../') . '/', '#: ', $potContents);

@unlink($listOfFiles);

echo $potContents;

/**
 * Recursive version of glob
 *
 * @param string $start_dir
 * @param string $pattern
 * @param int $flags
 * @return array
 */
function rglob($start_dir, $pattern, $flags = null)
{
    $start_dir = escapeshellcmd($start_dir);

    // Get the list of all matching files currently in the directory.
    $files = glob("$start_dir/$pattern", $flags);

    // Then get a list of all directories in this directory, and
    // run ourselves on the resulting array.  This is the
    // recursion step, which will not execute if there are no
    // directories.
    foreach (glob("$start_dir/*", GLOB_ONLYDIR) as $subdir) {
        $subfiles = rglob($subdir, $pattern, $flags);
        $files = array_merge($files, $subfiles);
    }

    // The array we return contains the files we found, and the
    // files all of our children found.
    return $files;
}
