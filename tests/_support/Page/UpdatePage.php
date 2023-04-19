<?php

namespace Page;

class UpdatePage
{
    const TITLE = "Update";
    const TITLE_XPATH = "//h3[contains(.,'Update')]";

    const DESCRIPTION_A = "On this page you can manually update the addon to the latest version.";
    const DESCRIPTION_B = "Auto-update is currently enabled. You can modify this in the configuration";

    const TIER_DROP_DOWN_XPATH = "//select[@id='update_type']";
    const TIER_DROP_DOWN_CSS   = "#update_type"; 

    const FORCE_REINSTALL_INPUT_XPATH = "//input[@id='force_reinstall']";
    const FORCE_REINSTALL_INPUT_CSS   = "#force_reinstall";


    const CLICK_TO_UPGRADE_BTN_XPATH = "//input[@id='submit']";
    const CLICK_TO_UPGRADE_BTN_CSS   = "#submit";
}
