<?php

/*
 * Update .po (and .pot template) translation files with new project content
 */

require_once __DIR__ . '/../library/SpamFilter/Translation/Update.php';

$updater = new SpamFilter_Translation_Update;
$updater->update(__DIR__ . '/../application/', __DIR__ .  '/../translations/');
