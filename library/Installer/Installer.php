<?php

class Installer_Installer
{
    public const API_TOKEN_ID = 'prospamfilter';

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

    public function __construct(Installer_InstallPaths $paths, Filesystem_AbstractFilesystem $filesystem, Output_OutputInterface $output)
    {
        $this->output = $output;
        $this->filesystem = $filesystem;
        $this->paths = $paths;
        $this->logger = Zend_Registry::get('logger');
        $this->findCurrentVersionAndInitPanelSupport();
    }

    /**
     * Install prospamfilter for CPanel
     */
    public function install()
    {
        try {
            $this->doInstall();
        } catch (Exception $exception) {
            $this->output->error($exception->getMessage());
            $this->logger->debug($exception->getMessage());
            $this->logger->debug($exception->getTraceAsString());
        }
    }

    private function doInstall()
    {
        $this->outputInstallStartMessage('cpanel');
        $this->upgrade();

        $this->checkRequirementsAreMet();
        $this->checkUserIsRoot();
        $this->checkOldVersionIsUninstalled();

        $this->checkOldConfigurationFile();
        $this->cleanupOldPluginDynamicUi();

        if (! $this->panelSupport->minVerCheck()) {
            $this->output->error("The currently used version of your controlpanel doesn't match minimum requirement.");
            exit(1);
        }

        $this->copyFilesToDestination();
        $this->changeDestinationPermissions();

        $this->setupConfigDirectory();

        $this->warnAboutUpgradeTier();
        $this->removeOldVersion();
        $this->symlinkAddonToWebDirs();
        $this->generateConfigurationFile();

        // Brand regeneration, since it may have been replaced and should not be default :-)
        $this->output->info("Generating cPanel plugin configuration..");
        $this->configureBranding();

        $this->output->info("Registering hooks ...");
        $hooks = array(
            'file' => $this->paths->destination . '/bin/hook.php',
            'do' => 'add',
            'hooks' => SpamFilter_PanelSupport_Cpanel::getHooksList()
        );
        $this->panelSupport->manageHooks($hooks);
        $this->output->ok("Done.");

        $this->registerWhmAppInAppConfig();
        $this->registerCpanelAppInAppConfig();

        /** @see https://trac.spamexperts.com/ticket/21395#comment:18 */
        if (file_exists('/usr/local/cpanel/whostmgr/addonfeatures/prospamfilter3')) {
            unlink('/usr/local/cpanel/whostmgr/addonfeatures/prospamfilter3');
        }

        file_put_contents('/usr/local/cpanel/whostmgr/addonfeatures/prospamfilter', 'prospamfilter:SpamExperts');

        $this->setUpApiTokens();
        $this->setUpdateCronjob();
        $this->setupSuidPermissions();
        $this->removeMigrationFiles($this->currentVersion);
        $this->removeSrcFolder();
        $this->removeInstallFileAndDisplaySuccessMessage();
    }

    private function setUpApiTokens()
    {
        $accessTokenFile = "/root/.accesstoken";
        $accessToken = '';
        if (file_exists($accessTokenFile)) {
            $accessToken = trim(file_get_contents($accessTokenFile));
        }
        if (empty($accessToken)) {
            $this->createApiAuthToken($accessTokenFile);
        } else {
            // check that the existing access token is actually valid and has its required acls
            // use curl instead of whmapi1 to prevent api token exposure in process lists (ps output)
            // since the connection is to localhost, ssl verification is disabled for performance
            $url = "https://127.0.0.1:2087/json-api/api_token_get_details?api.version=1&token=" . urlencode($accessToken);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    "Authorization: whm root:$accessToken"
                ],
            ]);

            $jsonOutput = curl_exec($ch);
            curl_close($ch);
            $tokenDetails = json_decode($jsonOutput, true);
            $data = $tokenDetails['data'] ?? [];
            $tokenOK = isset($data['acls'], $data['name'])
              && is_array($data['acls'])
              && in_array('all', $data['acls'], true)
              && $data['name'] === self::API_TOKEN_ID;
            if (!$tokenOK) {
                $this->createApiAuthToken($accessTokenFile);
            } else {
                $this->logger->debug("Access token for WHM API already exists and has correct permissions, skipping step.");
                $this->output->info("Access token for WHM API already exists and has correct permissions, skipping step.");
            }
        }
    }

    private function createApiAuthToken($accessTokenFile)
    {
        // revoke any existing token with the same name before creating a new one
        // this prevents the "A conflicting API token... already exists" error from whmapi1
        shell_exec("/usr/sbin/whmapi1 api_token_revoke token_name=" . self::API_TOKEN_ID);
        $jsonOutput = shell_exec("/usr/sbin/whmapi1 api_token_create token_name=" . self::API_TOKEN_ID . " acl-1=all --output=json");
        $output = json_decode($jsonOutput, true);

        if (!empty($output['metadata'])) {
            if ($output['metadata']['reason'] != "OK") {
                $this->logger->debug("Access token couldn't be created. Reason: " . $output['metadata']['reason']);
                $this->output->error("Access token couldn't be created. Reason: " . $output['metadata']['reason']);
            } else {
                $token = $output['data']['token'];

                $accessTokenFile = fopen($accessTokenFile, "w");
                fwrite($accessTokenFile, $token);
                fclose($accessTokenFile);

                $this->logger->debug("Access token was successfully created.");
                $this->output->info("Access token was successfully created.");
            }
        } else {
            $this->logger->debug("Error when using Whm Api call. Response: " . $jsonOutput);
            $this->output->info("Error when using Whm Api call. Response: " . $jsonOutput);
        }
    }

    private function findCurrentVersionAndInitPanelSupport()
    {
        $options = array('skipapi' => true);

        if (file_exists("/usr/local/prospamfilter2/application/version.txt")) {
            $this->logger->debug("[Install] PSF2 version file exists");
            $this->currentVersion = trim(file_get_contents("/usr/local/prospamfilter2/application/version.txt"));
            if (version_compare($this->currentVersion, '3.0.0', '<')) {
                $this->logger->debug("[Install] Version is below 3.0.0");
                if (file_exists('/etc/prospamfilter2/settings.conf')) {
                    $this->logger->debug("[Install] Changing config to old");
                    $options['altconfig'] = "/etc/prospamfilter2/settings.conf";
                }
            }
        } elseif (file_exists($this->paths->destination . DS . 'application' . DS . 'version.txt')) {
            $this->logger->debug("[Install] New version file found post 3.0.0");
            $this->currentVersion = trim(file_get_contents($this->paths->destination . DS . 'application' . DS . 'version.txt'));
        } else {
            $this->logger->debug("[Install] No version file found, must be new install.");
            $this->currentVersion = null; //no version set, must be an upgrade
        }

        $this->panelSupport = new SpamFilter_PanelSupport_Cpanel($options);
    }

    protected function upgrade()
    {
        if (! $this->shouldUpgrade()) {
            return;
        }

        $this->output->info("Going to upgrade addon 2.x to 3.0.x structure...");

        if (file_exists("/usr/src/prospamfilter2")) {
            $this->output->info("Removing old source files..");
            $this->filesystem->removeDirectory("/usr/src/prospamfilter2");
        }

        // Copy config folder to unversioned one
        $this->output->info("Copying up current configuration to new location");
        smartCopy("/etc/prospamfilter2", $this->paths->config);

        // Run uninstaller for the old addon to get rid of all traces
        if (!file_exists("/usr/local/prospamfilter2/bin/uninstall.php")) {
            $this->output->error("Unable to uninstall the old version.");
            exit(1);
        } else {
            $this->output->info("Patching old uninstaller to skip confirmations..");
            system("sed -i 's/.*confirm();.*/#&/g' /usr/local/prospamfilter2/bin/uninstall.php");
            system(
                "sed -i 's/.*This program can only be ran from CLI.*/#&/g' /usr/local/prospamfilter2/bin/uninstall.php"
            );
            $this->output->info("Running uninstaller to remove old version..");

            $curdir = getcwd(); // retrieve current dir
            $this->logger->info("[Install] Current directory: {$curdir}");
            if (chdir("/usr/local/prospamfilter2/bin/")) {
                $this->logger->info("[Install] Moved into /usr/local/prospamfilter2/bin");
                $lastline = system("./uninstall.php >/tmp/upgrade_psf 2>&1", $return_var); // run
                $this->logger->info("[Install] Uninstaller last line: {$lastline}");
                chdir($curdir); // Change back to previous directory
                $this->logger->info("[Install] Changed back to {$curdir}");
                $this->output->info("Removal of addon completed with exitcode: {$return_var}.");
            } else {
                $this->output->error("Cannot run uninstaller, directory does not exist or incorrect permissions.");
                exit(1);
            }
        }

        clearstatcache(); // Clear cache just to be sure
        if (file_exists("/usr/local/prospamfilter2/bin/uninstall.php")) {
            // Crap, that file should no longer exist
            $this->output->error("Old version was not uninstalled properly. This may lead to issues.");
            exit(1);
        }

        // Remove leftover from partially integrated feature.
        if (file_exists("/usr/local/cpanel/hooks/email/setmxcheck")) {
            unlink("/usr/local/cpanel/hooks/email/setmxcheck");
        }

        // Create a dummy page to redirect the user to in case the updater reloads

        $this->logger->debug("[Install] Going to create backwards compatibility support file..");
        $file_content   = array();
        $file_content[] = "<?php\n";
        $file_content[] = "header( \"refresh:5;url=/cgi/addon_prospamfilter.cgi\" );\n";
        $file_content[] = 'echo "You\'ll be redirected in about 5 secs. If not, click <a href=\\"/cgi/addon_prospamfilter.cgi\\">here</a>.";' . "\n";
        $file_content[] = "?>";
        $bw_status      = file_put_contents(
            "/usr/local/cpanel/whostmgr/docroot/cgi/addon_prospamfilter2.php",
            $file_content
        );
        $this->logger->debug("[Install] Creating backwards compatibility file resulted in code: {$bw_status}");

        // Merge config file with apipass file
        $passfile = file_get_contents($this->paths->config . "/apipass.conf");
        if (file_put_contents($this->paths->config . "/settings.conf", $passfile, FILE_APPEND)) {
            $this->output->info("Removing old password file..");
            unlink("/etc/prospamfilter/apipass.conf");

            $this->updateSettingsFilePermissions();
        }

        // Copy branding to separate file.
        $this->output->info("Migrating branding..");
        system("egrep '(brandname|brandicon)' /etc/prospamfilter/settings.conf > /etc/prospamfilter/branding.conf");

        // Create Spampanel user (#10002)
        $this->output->info("Going to migrate API user...");

        $config = Zend_Registry::get('general_config');
        $api    = new SpamFilter_ResellerAPI();
        if ($api) {
            if (!isset($config)) {
                $this->output->error("Cannot migrate API user: Missing configuration");
            } elseif (!isset($config->apiuser)) {
                $this->output->error("Cannot migrate API user: Missing configuration: username");
            } elseif (!isset($config->apipass)) {
                $this->output->error("Cannot migrate API user: Missing configuration: password");
            } else {
                $data     = array(
                    'username'     => $config->apiuser,
                    'password'     => urlencode($config->apipass), // hopefully this works
                    'email'        => 'root@' . SpamFilter_Core::GetServerName(),
                );
                $response = $api->user()->adminadd($data);
                if ($response['status'] && $response['reason'] = "OK") {
                    $this->output->ok("The API user has been migrated");
                } else {
                    $this->output->info("API migration response: " . serialize($response));
                }
            }
        } else {
            $this->output->error("Cannot migrate API user: No API connection");
            sleep(2);
        }

        // Completed.
        $this->output->ok("Addon 2.x to 3.0 upgrade has completed succesfully.");
    }

    protected function shouldUpgrade()
    {
        if (!isset($this->currentVersion) || (empty($this->currentVersion))) {
            $this->output->info("No need to update the structure of the addon, there is no previous version.");

            return false;
        }

        if (file_exists("/usr/local/prospamfilter/application/bootstrap.php")) {
            $this->output->info("No need to update the structure of the addon, we are already past the upgrade point.");

            return false;
        }

        // Check if we are below 3.x.x
        if (!version_compare($this->currentVersion, '3.0.0', '<')) {
            $this->output->info("No need to update the structure of the addon, we are already at the correct version.");

            return false;
        }

        return true;
    }

    private function checkOldConfigurationFile()
    {
        // Before cleanup we must check there is no an old cpanelplugin file
        if (file_exists("/usr/local/prospamfilter/frontend/cpanel/cpanel11/prospamfilter.cpanelplugin")) {
            if (strstr(file_get_contents('/usr/local/prospamfilter/frontend/cpanel/cpanel11/prospamfilter.cpanelplugin'), 'name:prospamfilter3') === false) {
                return;
            }

            // And unregister plugin with no valid name
            $this->output->info("Old configuration file found. Unregistering old plugin.");
            shell_exec("/usr/local/cpanel/bin/unregister_cpanelplugin /usr/local/prospamfilter/frontend/cpanel/cpanel11/prospamfilter.cpanelplugin");
            $this->output->ok("Done.");
        }

        return;
    }

    private function cleanupOldPluginDynamicUi()
    {
        // Cleanup dynamicui for old plugin
        $cPanelWebdirsRoot = '/usr/local/cpanel/base/frontend';
        foreach (scandir($cPanelWebdirsRoot) as $eachDir) {
            if (is_dir($cPanelWebdirsRoot . '/' . $eachDir)) {
                if (file_exists($cPanelWebdirsRoot . '/' . $eachDir . '/dynamicui/dynamicui_prospamfilter3.conf')) {
                    unlink($cPanelWebdirsRoot . '/' . $eachDir . '/dynamicui/dynamicui_prospamfilter3.conf');
                }
            }
        }
    }

    private function setupConfigDirectory()
    {
        $this->output->info("Prepopulating config folder");
        $this->createConfigDirectory();

        $cfgdir = $this->paths->config;
        // Settings file (world readable, root writable)
        touch("{$cfgdir}" . DS . "settings.conf");
        $this->updateSettingsFilePermissions();
        // Create branding file
        touch("{$cfgdir}" . DS . "branding.conf");

        $this->output->ok("Done");
    }

    private function createConfigDirectory()
    {
        $directory = $this->paths->config;

        if (!file_exists($directory)) {
            @mkdir("{$directory}" . DS, 0755, true);
            if (!file_exists($directory)) {
                die("[ERROR] Unable to create config folder!");
            }
        }
    }

    private function updateSettingsFilePermissions()
    {
        chown($this->paths->config . "/settings.conf", "root");
        chgrp($this->paths->config . "/settings.conf", "root");
        chmod($this->paths->config . "/settings.conf", 0660);
    }

    private function getCpanelVersion()
    {
        $output = shell_exec("/usr/local/cpanel/cpanel -V");
        $x = explode(" ", $output);

        return trim($x[0]);
    }

    protected function warnAboutUpgradeTier()
    {
        // Check cPanel update type and warn the user about that.
        try {
            $cpaneltier = trim(shell_exec('grep "CPANEL=" /etc/cpupdate.conf'));
            $cpaneltier = explode("=", $cpaneltier);
            if (is_array($cpaneltier)) {
                // Good, the explode worked.
                $cpaneltier = strtolower(trim($cpaneltier[1]));
                if (!in_array($cpaneltier, array('stable', 'release', 'current', 'edge'))) {
                    $this->output->warn("\n** Warning: Your cPanel product tier is set to '{$cpaneltier}'.");
                    $this->output->warn("We can only support stable product tiers, since during cPanel development changes can be introduced.");
                    $this->output->warn("The installation will proceed, but there may be unwanted sideeffects");
                    echo "\n";
                    sleep(5);
                }
            }
        } catch (Exception $e) {
            // So, reading the file failed. Not a big deal to break on.
        }
    }

    private function removeOldVersion()
    {
        // Remove old leftover from < 2.0.8
        if (file_exists("/usr/local/cpanel/whostmgr/docroot/cgi/prospamfilter.php")) {
            $this->output->info("Cleaning up old file from < 2.0.8...");
            @unlink("/usr/local/cpanel/whostmgr/docroot/cgi/prospamfilter.php");
            $this->output->ok("Done.");
            sleep(3);
        }

        // Remove old leftover from < 2.6.0
        if (file_exists("/tmp/prospamfilter2/")) {
            $this->output->info("Cleaning up old logfolder from < 2.6.0..");
            $this->filesystem->removeDirectory("/tmp/prospamfilter2");
            $this->output->ok("Done.");
        }
    }

    private function configureBranding()
    {
        $this->output->info("Configuring brand");
        // Generate this file
        $brand = new SpamFilter_Brand();
        if ($brand->hasBrandingData()) {
            $branding = array();
            $branding['brandname'] = $brand->getBrandUsed();
            $branding['brandicon'] = $brand->getBrandIcon();
            if ($this->panelSupport->setBrand($branding)) {
                $this->output->ok("Done");
            } else {
                $this->output->error("The cPanel plugin configuration could not be generated.");
            }
        } else {
            $this->output->error("The branding configuration cannot be initialized.");
        }
    }

    /**
     * @return array
     */
    private function symlinkAddonToWebDirs()
    {
        $whm_docroot = "/usr/local/cpanel/whostmgr/docroot";
        $this->output->info("Symlinking cPanel addon to webdirs");

        // Symlink cPanel indexes to webdirs.
        $cPanelWebdirsRoot = '/usr/local/cpanel/base/frontend';
        $cPanelVersion = $this->getCpanelVersion();
        foreach (scandir($cPanelWebdirsRoot) as $eachDir) {
            if (is_dir("{$cPanelWebdirsRoot}/{$eachDir}")
                && !in_array($eachDir, array('.', '..'))
                && !is_link("{$cPanelWebdirsRoot}/{$eachDir}")
            ) {
                $this->output->info("Symlinking cPanel addon to webdir {$eachDir}");
                if (in_array($eachDir, ['paper_lantern', 'jupiter']) && version_compare($cPanelVersion, '11.44.2') == 1) {
                    $ret_val = $this->filesystem->symlink(
                        "/usr/local/prospamfilter/frontend/templatetoolkit/",
                        "{$cPanelWebdirsRoot}/{$eachDir}/prospamfilter"
                    );
                } else {
                    $ret_val = $this->filesystem->symlink(
                        "/usr/local/prospamfilter/frontend/cpaneltags/",
                        "{$cPanelWebdirsRoot}/{$eachDir}/prospamfilter"
                    );
                }
                $this->logger->info("[Install] Symlinking to {$eachDir} completed: " . $ret_val);
            }
        }

        // Symlink cpanel folder to cpaneltags and templatetoolkit directories
        $ret_val = $this->filesystem->symlink(
            "/usr/local/prospamfilter/frontend/cpanel/psf.php",
            "/usr/local/prospamfilter/frontend/cpaneltags/psf.php"
        );
        $this->logger->info("[Install] Symlinking to /usr/local/prospamfilter/frontend/cpaneltags/psf.php completed: " . $ret_val);
        $ret_val = $this->filesystem->symlink(
            "/usr/local/prospamfilter/frontend/cpanel/psf.php",
            "/usr/local/prospamfilter/frontend/templatetoolkit/psf.php"
        );
        $this->logger->info("[Install] Symlinking to /usr/local/prospamfilter/frontend/templatetoolkit/psf.php completed: " . $ret_val);

        // Symlink WHM addon to CGI dir.
        $this->output->info("Symlinking WHM addon to webdir");
        $ret_val = $this->filesystem->symlink(
            "/usr/local/prospamfilter/frontend/whm/prospamfilter.php",
            $whm_docroot . "/cgi/addon_prospamfilter.cgi"
        );
        $this->logger->info("[Install] Symlinking to WHM webdir completed: " . $ret_val);

        // Symlink WHM icons to CGI dir.
        $this->output->info("Symlinking WHM addon icons to webdir");
        $ret_val = $this->filesystem->symlink("/usr/local/prospamfilter/public/images", $whm_docroot . "/cgi/psf");
        $this->logger->info("[Install] Symlinking icons to webdir completed with {$ret_val}");

        // Symlink cPanel icons to CGI dir.
        $this->output->info("Symlinking cPanel addon icons to webdir");
        $ret_val = $this->filesystem->symlink(
            "/usr/local/prospamfilter/public/images",
            "/usr/local/prospamfilter/frontend/templatetoolkit/psf"
        );
        $this->logger->info("[Install] Symlinking cPanel addon icons to webdir templatetoolkit completed with {$ret_val}");
        $this->output->info("Symlinking cPanel addon icons to webdir");
        $ret_val = $this->filesystem->symlink(
            "/usr/local/prospamfilter/public/images",
            "/usr/local/prospamfilter/frontend/cpaneltags/psf"
        );
        $this->logger->info("[Install] Symlinking cPanel addon icons to webdir cpaneltags completed with {$ret_val}");

        $this->output->info("Symlinking cPanel addon vendor js to webdir");
        $ret_val = $this->filesystem->symlink(
            "/usr/local/prospamfilter/public/js",
            "/usr/local/prospamfilter/frontend/templatetoolkit/vendor"
        );
        $this->logger->info("[Install] Symlinking cPanel addon vendor js to webdir templatetoolkit completed with {$ret_val}");

        // Make it executable (requirement for .cgi file)
        $this->output->info("Making WHM addon executable");
        system("chmod +x {$whm_docroot}/cgi/addon_prospamfilter.cgi", $ret_val);
        $this->logger->info("[Install] Executable setting (addon_prospamfilter.cgi) returned with {$ret_val}");

        $this->output->info("Symlinking WHM icon to webdir");
        $ret_val = $this->filesystem->symlink(
            "/usr/local/prospamfilter/public/images/prospamfilter.gif",
            $whm_docroot . "/themes/x/icons/prospamfilter.gif"
        );
        $this->logger->info("[Install] Symlink (WHM icon -> webdir) returned with {$ret_val}");
    }

    private function generateConfigurationFile()
    {
        $file = $this->paths->config.DS."settings.conf";

        if ((!file_exists($file)) || (filesize($file) == 0)) {
            $this->output->info("Configuration file '" . $file . "' does not exist (or is empty).");
            $this->output->info("Generating default configuration file..");
            $cfg = new SpamFilter_Configuration($file);

            if ($cfg) {
                $this->output->info("Configuring initial settings..");
                $cfg->setInitial();
                $this->output->ok("Done!");
            } else {
                $this->output->error("Failed!");
            }
        } else {
            $this->output->info("Configuration file '" . $file . "' already exists.");
        }
    }

    /**
     * @return array
     */
    private function registerWhmAppInAppConfig()
    {
        /** Register the WHM application in the AppConfig system */
        $statusAppConfig = trim(`/usr/local/cpanel/bin/is_registered_with_appconfig whostmgr prospamfilter_whm`);
        if ('0' == $statusAppConfig) {
            $appConfigFile = $this->getBinPath() . '/cpanel/appconfig/prospamfilter_whm.conf';
            $appConfigRegisterResult = trim(shell_exec("/usr/local/cpanel/bin/register_appconfig $appConfigFile"));

            if (false === stripos($appConfigRegisterResult, 'prospamfilter_whm registered')) {
                throw new RuntimeException('Failed to register the WHM application in the AppConfig: ' . $appConfigRegisterResult);
            }

            $this->output->ok("The WHM app has been successfully registered in the AppConfig registry");
        } else {
            $this->output->info("The WHM app in already registered in the AppConfig registry");
        }
    }

    private function registerCpanelAppInAppConfig()
    {
        /** Register the Cpanel application in the AppConfig system */
        $binPath = $this->getBinPath();
        $cPanelWebdirsRoot = '/usr/local/cpanel/base/frontend';
        foreach (scandir($cPanelWebdirsRoot) as $eachDir) {
            if (is_dir("{$cPanelWebdirsRoot}/{$eachDir}")
                && !in_array($eachDir, array('.', '..', 'x3.bak'))
                && !is_link("{$cPanelWebdirsRoot}/{$eachDir}")
            ) {
                $statusAppConfig = trim(shell_exec(
                    "/usr/local/cpanel/bin/is_registered_with_appconfig cpanel prospamfilter_cpanel_{$eachDir}"
                ));
                if ('0' == $statusAppConfig) {
                    $appConfigFile = $binPath . '/cpanel/appconfig/prospamfilter_cpanel.conf';

                    file_put_contents(
                        $binPath . "/cpanel/appconfig/prospamfilter_cpanel_{$eachDir}.conf",
                        strtr(file_get_contents($appConfigFile), array('%template%' => $eachDir))
                    );

                    $appConfigFile = $binPath . "/cpanel/appconfig/prospamfilter_cpanel_{$eachDir}.conf";
                    $appConfigRegisterResult = trim(shell_exec("/usr/local/cpanel/bin/register_appconfig $appConfigFile"));

                    if (false === stripos($appConfigRegisterResult, "prospamfilter_cpanel_{$eachDir} registered")) {
                        $this->output->error("Failed to register the Cpanel_{$eachDir} application in the AppConfig: ".$appConfigRegisterResult);
                    } else {
                        $this->output->ok("The Cpanel_{$eachDir} app has been successfully registered in the AppConfig registry");
                    }
                } else {
                    $this->output->info("The Cpanel_{$eachDir} app in already registered in the AppConfig registry");
                }
            }
        }
    }

    private function getBinPath()
    {
        return __DIR__.'/../../bin';
    }

    private function checkRequirementsAreMet()
    {
        // We *need* shell_exec.
        if (!function_exists('shell_exec')) {
            $this->output->error('shell_exec function is required for the installer to work.');
            exit(1);
        }

        try {
            $whoami = shell_exec('whoami');
        } catch (Exception $e) {
            $this->output->error(
                "Error checking current user (via whoami), do you have the command 'whoami' or did you disallow shell_exec?."
            );
            exit(1);
        }

        // More detailed testing
        $selfcheck = SpamFilter_Core::selfCheck(false, array('skipapi' => true));
        $this->output->info("Running selfcheck...");
        if ($selfcheck['status'] != true) {
            if ($selfcheck['critital'] == true) {
                $this->output->error("Failed\nThere are some issues detected, of whom there are critical ones:");
            } else {
                $this->output->warn("Warning\nThere are some (potential) issues detected:");
            }

            foreach ($selfcheck['reason'] as $issue) {
                echo " * {$issue}\n";
            }

            // Wait a short wile.
            sleep(10);
        } else {
            $this->output->ok("Finished without errors");
        }
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

    private function checkOldVersionIsUninstalled()
    {
        if (file_exists('/usr/local/prospamfilter/uninstall.sh')) {
            die("Please uninstall the old version first (using 'sh /usr/local/prospamfilter/uninstall.sh') before installing this version.");
        }
    }

    private function copyFilesToDestination()
    {
        $this->output->info("Copying files to destination.");

        // Cleanup destination folder.
        $this->output->info("Cleaning up old folder (" . $this->paths->destination . ")..");
        $this->filesystem->removeDirectory($this->paths->destination);
        $this->output->ok("Done");

        // Make new destination folder
        $this->output->info("Creating new folder (" . $this->paths->destination . ")..");
        if (!file_exists($this->paths->destination)) {
            if (!mkdir($this->paths->destination, 0777, true)) {
                $this->output->error("Unable to create destination folder.");
                exit(1);
            }
        }
        $this->output->ok("Done");

        // Move all files to the new location.

        $this->output->info("Copying files from '" . $this->paths->base . "' to '" . $this->paths->destination . "'...");
        smartCopy($this->paths->base, $this->paths->destination);

        $this->output->ok("Done");

        return true;
    }

    private function setUpdateCronjob()
    {
        $cronfile = "/etc/cron.d/prospamfilter";
        // Create crontab entry
        $hour = mt_rand(20, 23); // Decide on update hour (between 20 and 23)
        $min = mt_rand(0, 59); // Decide on update minutes (0 to 59)

        /** @see https://trac.spamexperts.com/ticket/24064 */
        $dayOfWeek = mt_rand(0, 6);

        /*
        // Test code to make sure the updater runs in 5 minutes after installing
        $test_time 	= strtotime("+5 minutes");
        $hour 		= date('H', $test_time);
        $min 		= date('i', $test_time);
        */

        $data = "## Skip mailing this cron output to prevent numerous emails\n";
        $data .= "#MAILTO=\"\"\n";
        $data .= "\n";
        $data .= "#TZ=UTC\n";
        $data .= "\n";
        $data .= "#TZ=" . date_default_timezone_get() . "\n";
        $data .= "## Automatically update Professional Spam Filter (if enabled in the settings)\n";
        $data .= "{$min} {$hour} * * {$dayOfWeek} root /usr/local/prospamfilter/bin/checkUpdate.php\n";

        // Write entry to crontab file.
        file_put_contents($cronfile, $data);
        $this->output->ok("Done");

        // Reload cron since placing a file does not work. (Support #711724)
        $this->output->info("Reloading cron...");
        touch("/etc/cron.d/prospamfilter");
        $cron = touch("/etc/cron.d"); // just in case

        $retval = '';

        // Here be some additional reload checks
        if (file_exists('/etc/init.d/crond')) {
            $this->output->info("Reloading cron via daemon reload (crond)");
            // In some cases, touching it does not work, so reloading must.
            system('/etc/init.d/crond reload >/dev/null', $retval);
        } elseif (file_exists('/etc/init.d/cron')) {
            $this->output->info("Reloading cron via daemon reload (cron)");
            // In some cases, touching it does not work, so reloading must.
            system('/etc/init.d/cron reload >/dev/null', $retval);
        }
        $this->output->info("Cron reload returned value {$retval}");

        if ($cron) {
            $this->output->ok("Done");
        } else {
            $this->output->error("FAILED!");
            $this->output->warn("** Unable to reload cron **");
            $this->output->warn("--> Please rotate the cron daemon to make sure the cronjob is loaded properly.");
            sleep(10);
        }
    }

    private function setupSuidPermissions()
    {
        $this->output->info("Setting up permissions for SUID binaries...");

        if (file_exists($this->paths->destination . DS . "bin" . DS . "getconfig64")) {
            $this->output->info("64-bits...");
            chown($this->paths->destination . "/bin/getconfig64", "root");
            chgrp($this->paths->destination . "/bin/getconfig64", "root");
            system("chmod 755 " . $this->paths->destination . "/bin/getconfig64");
            system("chmod +s " . $this->paths->destination . "/bin/getconfig64");
        }

        if (file_exists($this->paths->destination . "/bin/getconfig")) {
            $this->output->info("32-bits...");
            chown($this->paths->destination . "/bin/getconfig", "root");
            chgrp($this->paths->destination . "/bin/getconfig", "root");
            system("chmod 755 " . $this->paths->destination . "/bin/getconfig");
            system("chmod +s " . $this->paths->destination . "/bin/getconfig");
        }

        $this->output->ok("Done");
    }

    private function removeMigrationFiles($version)
    {
        if (version_compare($version, '3.0.0', '>')) {
            if (file_exists('/usr/local/cpanel/whostmgr/docroot/cgi/addon_prospamfilter2.php')) {
                $this->output->info("Removing some traces from PSF2->PSF3 migration.");
                unlink("/usr/local/cpanel/whostmgr/docroot/cgi/addon_prospamfilter2.php");
            }
        }
    }

    private function removeSrcFolder()
    {
        $this->output->info("Removing temporary files");
        $this->filesystem->removeDirectory($this->paths->base);
        $this->output->ok("Done");
    }

    private function removeInstallFileAndDisplaySuccessMessage()
    {
        // Remove installer as we do not need it anymore.
        unlink($this->paths->destination . "/bin/install.php");
        $this->output->ok("\n\n***** Congratulations, Professional Spam Filter for Cpanel has been installed on your system! *****");
        $this->output->ok("If the addon is not configured yet, you should setup initial configuration in the admin part of the control panel before using its features.");
    }

    private function outputInstallStartMessage($panelType)
    {
        $version = $this->getVersion();
        $this->output->info("This system will install Professional Spam Filter v{$version} for {$panelType}");
    }

    private function changeDestinationPermissions()
    {
        $permissions = array(
            0754 => array(
                'bin/bulkprotect.php',
                'bin/uninstall.php',
            ),
            0755 => array(
                'bin/checkUpdate.php',
                'bin/installer/installer.sh',
                'bin/hook.php',
                'frontend/cpanel/psf.php',
                'frontend/whm/prospamfilter.php',
            ),
        );

        foreach ($permissions as $permission => $files) {
            foreach ($files as $file) {
                @chmod($this->paths->destination . DS . $file, $permission);
            }
        }

        $this->output->info("Setting tmp folder permissions");
        // Chmod the tmp folder (and everything in it)
        system("chmod -R 1777 " . $this->paths->destination . DS . "tmp");
        $this->output->ok("Done");
    }

    private function getVersion()
    {
        return trim(file_get_contents($this->paths->base . DS . "application" . DS . "version.txt"));
    }
}
