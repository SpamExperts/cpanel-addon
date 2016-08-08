<?php

namespace Page;

class UpdatePage
{
    const TITLE = "Update";
    const DESCRIPTION_A = "On this page you can manually update the addon to the latest version.";
    const DESCRIPTION_B = "Auto-update is currently disabled. You can modify this in the configuration";

    const TIER_DROP_DOWN        = "//select[@id='update_type']";
    const FORCE_REINSTALL_INPUT = "//input[@id='force_reinstall']";
    const CLICK_TO_UPGRADE_BTN  = "//input[@id='submit']";

    const WIN_DESCRIPTION_A = "Download the following file:";
    const WIN_DESCRIPTION_URL = "http://download.seinternal.com/integration/installers/plesk-windows/installer.bat";
    const WIN_DESCRIPTION_B = "From the command line run the following command:";
    const WIN_DESCRIPTION_COMMAND = "cmd /k installer.bat";
}
