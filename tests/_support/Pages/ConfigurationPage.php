<?php

namespace Pages;

class ConfigurationPage
{
    const TITLE = "Configuration";
    const DESCRIPTION_A = "On this page you can configure the admin settings of the addon.";
    const DESCRIPTION_B = "You can hover over the options to see more detailed information about what they do.";

    const ANTISPAM_API_URL      = "//input[@data-original-title='AntiSpam API URL']";
    const API_HOSTNAME          = "//input[@data-original-title='API hostname']";
    const API_USERNAME          = "//input[@data-original-title='API username']";
    const API_PASSWORD          = "//input[@data-original-title='API password']";
    const MX_PRIMARY            = "//input[@data-original-title='Primary MX']";
    const MX_SECONDARY          = "//input[@data-original-title='Secondary MX']";
    const MX_TERTIARY           = "//input[@data-original-title='Tertiary MX']";
    const MX_QUATERNARY         = "//input[@data-original-title='Quaternary MX']";
    const SPF_RECORD            = "//input[@data-original-title='SPF Record']";
    const TTL_FOR_MX            = "//select[@data-original-title='TTL to use for MX records']";
    const LANGUAGE_DROP_DOWN    = "//select[@data-original-title='Language']";

    const ENABLE_SSL_FOR_API_OPT            = "//input[@data-original-title='SSL']";
    const ENABLE_AUTOMATIC_UPDATES_OPT      = "//input[@data-original-title='Enable automatic updates']";
    const AUTOMATICALLY_ADD_DOMAINS_OPT     = "//input[@data-original-title='Automatically add domains to the Spamfilter']";
    const AUTOMATICALLY_DELETE_DOMAINS_OPT  = "//input[@data-original-title='Automatically delete domains from the SpamFilter']";
    const AUTOMATICALLY_CHANGE_MX_OPT       = "//input[@data-original-title='Automatically change the MX records for domains']";
    const CONFIGURE_EMAIL_ADDRESS_OPT       = "//input[@data-original-title='Configure the email address for this domain']";
    const PROCESS_ADDON_PLESK_OPT           = "//input[@data-original-title='Process addon- and parked domains']";
    const PROCESS_ADDON_CPANEL_OPT          = "//input[@data-original-title='Process addon-, parked and subdomains']";
    const ADD_ADDON_PLESK_OPT               = "//input[@data-original-title='Add addon- and parked domains as an alias instead of a normal domain.']";
    const ADD_ADDON_CPANEL_OPT              = "//input[@data-original-title='Add addon-, parked and subdomains as an alias instead of a normal domain.']";
    const USE_EXISTING_MX_OPT               = "//input[@data-original-title='Use existing MX records as routes in the spamfilter.']";
    const DO_NOT_PROTECT_REMOTE_DOMAINS_OPT = "//input[@data-original-title='Do not protect remote domains']";
    const REDIRECT_BACK_TO_PLESK_OPT        = "//input[@data-original-title='Redirect back to Plesk upon logout']";
    const REDIRECT_BACK_TO_CPANEL_OPT       = "//input[@data-original-title='Redirect back to Cpanel upon logout']";
    const ADD_DOMAIN_DURING_LOGIN_OPT       = "//input[@data-original-title='Add the domain to the spamfilter during login if it does not exist']";
    const FORCE_CHANGE_MX_ROUTE_OPT         = "//input[@data-original-title='Force changing route &amp; MX records, even if the domain exists.']";
    const CHANGE_EMAIL_ROUTING_OPT          = "//input[@data-original-title='Change email routing setting \"auto\" to \"local\" in bulk protect.']";
    const ADD_REMOVE_DOMAIN                 = "//input[@data-original-title='Add/Remove a domain when the email routing is changed in Cpanel']";
    const DISABLE_ADDON_IN_CPANEL           = "//input[@data-original-title='Disable addon in cPanel for reseller accounts.Cpanel']";
    const USE_IP_AS_DESTINATION_OPT         = "//input[@data-original-title='Use IP as destination route instead of domain']";
    const SET_SPF_RECORD                    = "//input[@data-original-title='Set SPF automatically for domains']";

    const SAVE_SETTINGS_BTN             = "//input[@class='btn btn-primary btn btn-primary']";
    const ERROR_MESSAGE_CONTAINER       = "//div[@class='alert alert-error alert-danger']";
    const SUCCESS_MESSAGE_CONTAINER     = "//div[@class='alert alert-success']";
    const OPT_ERROR_MESSAGE_CONTAINER   = "//div[@class='error control-group']";
}
