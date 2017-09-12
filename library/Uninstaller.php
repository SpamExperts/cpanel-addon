<?php

class Uninstaller
{
    /**
     * @var Filesystem_AbstractFilesystem
     */
    protected $filesystem;

    /**
     * @var Installer_InstallPaths
     */
    protected $paths;

    /**
     * @var SpamFilter_PanelSupport_Cpanel
     */
    protected $panelSupport;

    /**
     * @var SpamFilter_Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $currentVersion;

    /**
     * @var Output_OutputInterface
     */
    protected $output;

    /**
     * @var bool
     */
    protected $resetMx;

    public function __construct(Installer_InstallPaths $paths, Filesystem_AbstractFilesystem $filesystem, Output_OutputInterface $output, $resetMx = false)
    {
        $this->output = $output;
        $this->filesystem = $filesystem;
        $this->paths = $paths;
        $this->logger = Zend_Registry::get('logger');
        $this->resetMx = $resetMx;
    }

    public function uninstall()
    {
        try {
            $this->doUninstall();
        } catch (Exception $exception) {
            $this->output->error($exception->getMessage());
            $this->logger->debug($exception->getMessage());
            $this->logger->debug($exception->getTraceAsString());
        }
    }

    private function doUninstall()
    {
        $this->outputStartMessage();
        $this->checkRequirementsAreMet();
        $this->resetMXs();
        $this->removeAddonFromCpanel();
        $this->removeHooks();
        $this->unregisterCpanelAppInAppConfig();
        $this->filesystem->removeDirectory($this->paths->config);
        $this->filesystem->removeDirectory($this->paths->destination);
        $this->removeUpdateCronjob();
        $this->revokeApiToken();
        $this->output->write("\n\n***** We're sad to see you go, but ProSpamFilter has now been uninstalled from your system! *****\n\n");
    }

    private function outputStartMessage()
    {
        $this->output->write("*** Uninstallation Process ***");
        $this->output->write("This application will UNINSTALL your ProSpamFilter with full configuration.");
        $this->output->write('');

        $file = __DIR__.'/../application/version.txt';
        $version = file_get_contents($file);
        $this->output->write("This system will uninstall ProSpamFilter v{$version}");
    }

    private function checkRequirementsAreMet()
    {
        if(! file_exists($this->paths->destination)) {
            throw new Exception("ProSpamFilter is not installed");
        }

        $this->checkUserIsRoot();

        if (! is_cli()) {
            throw new Exception("This program can only be ran from CLI");
        }

        // Additional check for resetMX action.
        if($this->resetMx){
            if(! class_exists("SpamFilter_Hooks") || ! class_exists("SpamFilter_PanelSupport_Cpanel")){
                throw new Exception("ERROR: Required files are missing! Uninstaller cannot reset mx records!");
            }

            if(! function_exists("mb_strtolower")){
                throw new Exception("Multibyte Extension is required to proceed this action! Please install Multibyte Extenstion.");
            }
        }
    }

    private function resetMXs()
    {
        if (! $this->resetMx) {
            return;
        }

        $this->output->info("Resetting domains MX records. Please wait... ");
        $hooks = new SpamFilter_Hooks;
        $panel = new SpamFilter_PanelSupport_Cpanel();
        $failures = array();

        $domains = $panel->getDomains(array('username' => SpamFilter_Core::getUsername(), 'level' => 'owner'));

        if(empty($domains)){
            $this->output->ok("There are no domains. Skipping this step.");
            return;
        }

        foreach ($domains as $domain){
            $this->output->info("Resetting: " . $domain['domain']);
            $result = $hooks->DelDomain(trim($domain['domain']), true, true);

            //If domain isn't added to SpamFilter then it isn't error
            if($result['status'] != 1 && $result['reason'] != 'NO_SUCH_DOMAIN'){
                $failures[] = array('domain' => $domain['domain'], 'reason' => $result['reason']);
            }
        }

        if(empty($failures)) {
            $this->output->ok("MXs records was successfully reset!");
        } else {
            $this->output->error("MX records reset failed for domains:");

            foreach($failures as $fail){
                $this->output->write("  " . $fail['domain'] . " - " . $fail['reason']);
            }
        }

        $this->output->ok("Done");
    }

    private function checkUserIsRoot()
    {
        $whoami = shell_exec('whoami');
        $whoami = trim($whoami);
        if ($whoami != "root") {
            $this->output->error("This can only be installed by 'root' (not: '$whoami').");
            exit(1);
        }
    }

    private function removeAddonFromCpanel()
    {
        // Delink cPanel addon from webdirs.
        $webDirs = array(
            "/usr/local/cpanel/base/frontend/x3/prospamfilter/",
            "/usr/local/cpanel/base/frontend/x3mail/prospamfilter/",
            "/usr/local/cpanel/whostmgr/docroot/cgi/psf/",
            "/usr/local/prospamfilter/frontend/cpanel/psf",
        );

        foreach ($webDirs as $dir) {
            $this->output->info("Unlinking $dir");
            @unlink($dir);
        }

        // Delink WHM addon to CGI dir.
        @unlink("/usr/local/cpanel/whostmgr/docroot/cgi/addon_prospamfilter.php");
        @unlink("/usr/local/cpanel/whostmgr/docroot/cgi/addon_prospamfilter.cgi");

        // Delink WHM icon
        @unlink("/usr/local/cpanel/whostmgr/docroot/themes/x/icons/prospamfilter.gif");

        // Unregister cPanel addon (makes icon sprites)
        system("/usr/local/cpanel/bin/unregister_cpanelplugin /usr/local/prospamfilter/frontend/cpanel/cpanel11/prospamfilter.cpanelplugin");

        // Removes DynamicUI for paper_lantern theme
        @unlink("/usr/local/cpanel/base/frontend/paper_lantern/dynamicui/dynamicui_psf.conf");

        // Refresh cache (cPanel Bug: #1049678)
        system("touch /usr/local/cpanel/base/frontend/x3/dynamicui.conf");
        system("touch /usr/local/cpanel/base/frontend/x3mail/dynamicui.conf");
    }

    private function removeHooks()
    {
        $panel = new SpamFilter_PanelSupport_Cpanel();
        $hooks = array('file' => DEST_PATH . '/bin/hook.php',
            'do' => 'delete',
            'hooks' => array(
                array('category' => 'Whostmgr',
                    'event' => 'Accounts::Create',
                    'stage' => 'post',
                    'action' => ''),

                array('category' => 'Whostmgr',
                    'event' => 'Accounts::Remove',
                    'stage' => 'pre',
                    'action' => ''),

                array('category' => 'Whostmgr',
                    'event' => 'Accounts::Modify',
                    'stage' => 'post',
                    'action' => ''),

                array('category' => 'PkgAcct',
                    'event' => 'Restore',
                    'stage' => 'post',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api1::Park::park',
                    'stage' => 'post',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api1::Park::unpark',
                    'stage' => 'pre',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api2::AddonDomain::addaddondomain',
                    'stage' => 'post',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api2::AddonDomain::deladdondomain',
                    'stage' => 'pre',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api1::SubDomain::addsubdomain',
                    'stage' => 'post',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api2::SubDomain::addsubdomain',
                    'stage' => 'post',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api2::SubDomain::delsubdomain',
                    'stage' => 'pre',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api2::CustInfo::savecontactinfo',
                    'stage' => 'post',
                    'action' => ''),

                array('category' => 'Cpanel',
                    'event' => 'Api2::Email::setmxcheck',
                    'stage' => 'post',
                    'action' => ''),

            )
        );

        $panel->manageHooks($hooks);
    }

    private function unregisterCpanelAppInAppConfig()
    {
        $cpanelAppsToUnregister = array();
        $cPanelWebdirsRoot = '/usr/local/cpanel/base/frontend';

        foreach (scandir($cPanelWebdirsRoot) as $eachDir) {
            if (is_dir("{$cPanelWebdirsRoot}/{$eachDir}")
                && !in_array($eachDir, array('.', '..', 'x3.bak'))
                && !is_link("{$cPanelWebdirsRoot}/{$eachDir}")
            ) {
                $cpanelAppsToUnregister[] = "prospamfilter_cpanel_{$eachDir}";
            }
        }

        foreach ($cpanelAppsToUnregister as $app) {
            $isConfigured = trim(shell_exec("/usr/local/cpanel/bin/is_registered_with_appconfig cpanel $app"));

            if ('0' == $isConfigured) {
                continue;
            }

            $output = trim(shell_exec("/usr/local/cpanel/bin/unregister_appconfig $app"));

            if (false === stripos($output, "$app unregistered")) {
                echo "Failed to unregister $app: \n".$output."\n";
            } else {
                echo "$app unregistered successfully\n";
            }
        }

        shell_exec("/usr/local/cpanel/bin/unregister_appconfig prospamfilter_whm");

        $phpSymlink = '/usr/local/bin/prospamfilter_php';

        if (is_link($phpSymlink)) {
            unlink($phpSymlink);
        }

        $cPanelWebdirsRoot = '/usr/local/cpanel/base/frontend';

        foreach (scandir($cPanelWebdirsRoot) as $eachDir) {
            if (is_dir("{$cPanelWebdirsRoot}/{$eachDir}")
                && !in_array($eachDir, array('.', '..'))
                && !is_link("{$cPanelWebdirsRoot}/{$eachDir}")
            ) {
                $link = "{$cPanelWebdirsRoot}/{$eachDir}/prospamfilter";

                if (is_link($link)) {
                    unlink($link);
                }

            }
        }

        $file = '/usr/local/cpanel/whostmgr/addonfeatures/prospamfilter';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function removeUpdateCronjob()
    {
        $this->output->info("Removing cronjob...");
        @unlink("/etc/cron.d/prospamfilter");
        $this->output->ok("Done");

        $this->output->info("Reloading cron...");
        $cron = touch("/etc/crontab"); //Ha! Sneaky way of reloading which is univeral at CentOS/Debian :-)
        if ($cron) {
            $this->output->ok("Done");
        } else {
            $this->output->error("FAILED!");
            $this->output->error("** Unable to reload cron **");
            $this->output->error("--> Please rotate the cron daemon to make sure the cronjob is not longer being executed.");
            sleep(10);
        }
    }

    private function revokeApiToken()
    {
        $this->output->info("Rewoke api token...");

        $response = trim(shell_exec("whmapi1 api_token_revoke token_name=prospamfilter"));

        if (strpos($response, "result: 1") > -1) {
            @unlink("/root/.accesstoken");

            $this->output->info("Api token was succesfully rewoked.");
        } else {
            $this->output->error("Api token couldn't be rewoked. Please remove it manually from 'Manage API Tokens' page");
        }
    }
}