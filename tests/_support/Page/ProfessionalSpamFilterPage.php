<?php

namespace Page;

class ProfessionalSpamFilterPage
{
    const CONFIGURATION_BTN    = "//a[@href='?q=admin/settings']";
    const BRANDING_BTN         = "//a[@href='?q=admin/branding']";
    const DOMAIN_LIST_BTN      = "//a[@href='?q=reseller/listdomains']";
    const BULKPROTECT_BTN      = "//a[@href='?q=bulkprotect/index']";
    const MIGRATION_BTN        = "//a[@href='?q=admin/migrate']";
    const UPDATE_BTN           = "//a[@href='?q=admin/update']";
    const SUPPORT_BTN          = "//a[@href='?q=admin/support']";

    const PLUGIN_XPATH = "//span[contains(.,'Plugins')]";
    const PLUGIN_CSS   = ".//*[@id='plugins']/span";

    const CONFIGURATION_LINK = "//a[contains(.,'Configuration')]";
    const BRANDING_LINK      = "//a[contains(.,'Branding')]";
    const DOMAIN_LIST_LINK   = "//a[contains(.,'Domain List')]";
    const BULK_PROTECT_LINK  = "//a[contains(.,'Bulkprotect')]";
    const MIGRATION_LINK     = "//a[contains(.,'Migration')]";
    const UPDATE_LINK        = "//a[contains(.,'Update')]";
    const SUPPORT_LINK       = "//a[contains(.,'Support')]";
}
