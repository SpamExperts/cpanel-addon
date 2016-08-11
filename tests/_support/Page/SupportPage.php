<?php

namespace Page;

class SupportPage
{
    const TITLE       = "Support";
    const TITLE_XPATH = "//h3[contains(.,'Support')]";

    const DESCRIPTION = "In case you have issues with the addon, you can run a diagnostics on your installation prior to contacting support.";

    const TEXT_A = "Information";
    const TEXT_B = "Controlpanel:";
    const TEXT_C = "PHP version:";
    const TEXT_D = "Addon version:";
    const TEXT_E = "Diagnostics";

    const RUN_DIAGNOSTICS_BTN_XPATH = "//input[@id='diagnostics']";
    const RUN_DIAGNOSTICS_BTN_CSS   = "#diagnostics";

    const PHP_VERSION_XPATH = "//strong[contains(.,'PHP version:')]";
    const PHP_EXTENSIONS_XPATH = "//strong[contains(.,'PHP extensions:')]";
    const CONFIGURATION_BINARY_XPATH = "//strong[contains(.,'Configuration binary:')]";
    const CONFIGURATION_PERMISSIONS_XPATH = "//strong[contains(.,'Configuration permissions:')]";
    const PANEL_VERSION_XPATH = "//strong[contains(.,'Panel version:')]";
    const ADDON_VERSION_XPATH = "//strong[contains(.,'Addon version:')]";
    const HASHES_XPATH = "//strong[contains(.,'Hashes:')]";
    const HOOKS_XPATH = "//strong[contains(.,'Hooks:')]";
    const SYMLINKS_XPATH = "//strong[contains(.,'Symlinks:')]";
    const CONTROLPANEL_API_XPATH = "//strong[contains(.,'Controlpanel API:')]";
    const SPAMFILTER_API_XPATH = "//strong[contains(.,'Spamfilter API:')]";
    const DIAGNOSTIC_RESULT_XPATH = "//span[contains(.,'OK!')]";
}
