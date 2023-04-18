#!/usr/local/cpanel/3rdparty/bin/php
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
// Include requires
require_once(realpath(dirname(__FILE__) . '/../') . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'bootstrap.php');

defined('DEST_PATH') or define('DEST_PATH', '/usr/local/prospamfilter');
defined('BASE_PATH') or define('BASE_PATH', '/usr/src/prospamfilter');
defined('CFG_PATH') or define('CFG_PATH', '/etc/prospamfilter');

require_once DEST_PATH . DS . 'application' . DS . 'bootstrap.php';
require_once DEST_PATH . DS . 'library' . DS . 'SpamFilter' . DS . 'Core.php';
require_once DEST_PATH . DS . 'library' . DS . 'Installer' . DS . 'InstallPaths.php';
require_once DEST_PATH . DS . 'library' . DS . 'Filesystem' . DS . 'AbstractFilesystem.php';
require_once DEST_PATH . DS . 'library' . DS . 'Filesystem' . DS . 'LinuxFilesystem.php';
require_once DEST_PATH . DS . 'library' . DS . 'Filesystem' . DS . 'WindowsFilesystem.php';
require_once DEST_PATH . DS . 'library' . DS . 'Output' . DS . 'OutputInterface.php';
require_once DEST_PATH . DS . 'library' . DS . 'Output' . DS . 'ConsoleOutput.php';
require_once DEST_PATH . DS . 'library' . DS . 'Uninstaller.php';

$paths = new Installer_InstallPaths();
$paths->base = BASE_PATH;
$paths->destination = DEST_PATH;
$paths->config = CFG_PATH;
$filesystem = Filesystem_AbstractFilesystem::createFilesystem();
$output = new Output_ConsoleOutput();
$resetMx = !empty($argv) && isset($argv[1]) && trim(strtolower($argv[1])) == '--resetmx';
$uninstaller = new Uninstaller($paths, $filesystem, $output, $resetMx);
$uninstaller->uninstall();
