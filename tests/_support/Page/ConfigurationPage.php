<?php

namespace Page;

class ConfigurationPage
{
    const TITLE = "Configuration";
    const DESCRIPTION_A = "On this page you can configure the admin settings of the addon.";
    const DESCRIPTION_B = "You can hover over the options to see more detailed information about what they do.";

    // Fields locators
    const ANTISPAM_API_URL_FIELD_XPATH = "//*[@id='spampanel_url']";
    const ANTISPAM_API_URL_FIELD_CSS = "#spampanel_url";

    const API_HOSTNAME_FIELD_XPATH = "//*[@id='apihost']";
    const API_HOSTNAME_FIELD_CSS = "#apihost";

    const API_USERNAME_FIELD_XPATH = "//*[@id='apiuser']";
    const API_USERNAME_FIELD_CSS = "#apiuser";

    const API_PASSWORD_FIELD_XPATH = "//*[@id='apipass']";
    const API_PASSWORD_FIELD_CSS = "#apipass";

    const MX_PRIMARY_FIELD_XPATH = "//*[@id='mx1']";
    const MX_PRIMARY_FIELD_CSS = "#mx1";

    const MX_SECONDARY_FIELD_XPATH = "//*[@id='mx2']";
    const MX_SECONDARY_FIELD_CSS = "#mx2";

    const MX_TERTIARY_FIELD_XPATH = "//*[@id='mx3']";
    const MX_TERTIARY_FIELD_CSS = "#mx3";

    const MX_QUATERNARY_FIELD_XPATH = "//*[@id='mx4']";
    const MX_QUATERNARY_FIELD_CSS = "#mx4";

    const SPF_RECORD_FIELD_XPATH  = "//*[@id='spf_record']";
    const SPF_RECORD_FIELD_CSS = "#spf_record";

    const TTL_FOR_MX_DROP_DOWN_XPATH = "//*[@id='default_ttl']";
    const TTL_FOR_MX_DROP_DOWN_CSS = "#default_ttl";

    const LANGUAGE_DROP_DOWN_XPATH = "//*[@id='language']";
    const LANGUAGE_DROP_DOWN_CSS = "#language";

    // Checkboxes locators
    const ENABLE_SSL_FOR_API_OPT_XPATH  = "//*[@id='ssl_enabled']";
    const ENABLE_SSL_FOR_API_OPT_CSS = "#ssl_enabled";

    const ENABLE_AUTOMATIC_UPDATES_OPT_XPATH = "//*[@id='auto_update']";
    const ENABLE_AUTOMATIC_UPDATES_OPT_CSS = "#auto_update";

    const AUTOMATICALLY_ADD_DOMAINS_OPT_XPATH = "//*[@id='auto_add_domain']";
    const AUTOMATICALLY_ADD_DOMAINS_OPT_CSS = "#auto_add_domain";

    const AUTOMATICALLY_DELETE_DOMAINS_OPT_XPATH = "//*[@id='auto_del_domain']";
    const AUTOMATICALLY_DELETE_DOMAINS_OPT_CSS = "#auto_del_domain";

    const AUTOMATICALLY_CHANGE_MX_OPT_XPATH = "//*[@id='provision_dns']";
    const AUTOMATICALLY_CHANGE_MX_OPT_CSS = "#provision_dns";

    const CONFIGURE_EMAIL_ADDRESS_OPT_XPATH = "//*[@id='set_contact']";
    const CONFIGURE_EMAIL_ADDRESS_OPT_CSS = "#set_contact";

    const PROCESS_ADDON_CPANEL_OPT_XPATH = "//*[@id='handle_extra_domains']";
    const PROCESS_ADDON_CPANEL_OPT_CSS = "#handle_extra_domains";

    const ADD_ADDON_AS_ALIAS_CPANEL_OPT_XPATH = "//*[@id='add_extra_alias']";
    const ADD_ADDON_AS_ALIAS_CPANEL_OPT_CSS = "#add_extra_alias";

    const USE_EXISTING_MX_OPT_XPATH = "//*[@id='use_existing_mx']";
    const USE_EXISTING_MX_OPT_CSS = "#use_existing_mx";

    const DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_XPATH = "//*[@id='handle_only_localdomains']";
    const DO_NOT_PROTECT_REMOTE_DOMAINS_OPT_CSS = "#handle_only_localdomains";

    const REDIRECT_BACK_TO_CPANEL_OPT_XPATH = "//*[@id='redirectback']";
    const REDIRECT_BACK_TO_CPANEL_OPT_CSS = "#redirectback";

    const ADD_DOMAIN_DURING_LOGIN_OPT_XPATH = "//*[@id='add_domain_loginfail']";
    const ADD_DOMAIN_DURING_LOGIN_OPT_CSS = "#add_domain_loginfail";

    const FORCE_CHANGE_MX_ROUTE_OPT_XPATH = "//*[@id='bulk_force_change']";
    const FORCE_CHANGE_MX_ROUTE_OPT_CSS  = "#bulk_force_change";

    const CHANGE_EMAIL_ROUTING_OPT_XPATH = "//*[@id='bulk_change_routing']";
    const CHANGE_EMAIL_ROUTING_OPT_CSS = "#bulk_change_routing";

    const ADD_REMOVE_DOMAIN_XPATH = "//*[@id='handle_route_switching']";
    const ADD_REMOVE_DOMAIN_CSS = "#handle_route_switching";

    const DISABLE_ADDON_IN_CPANEL_XPATH = "//*[@id='disable_reseller_access']";
    const DISABLE_ADDON_IN_CPANEL_CSS = "#disable_reseller_access";

    const USE_IP_AS_DESTINATION_OPT_XPATH = "//*[@id='use_ip_address_as_destination_routes']";
    const USE_IP_AS_DESTINATION_OPT_CSS = "#use_ip_address_as_destination_routes";

    const SET_SPF_RECORD_XPATH = "//*[@id='add_spf_to_domains']";
    const SET_SPF_RECORD_CSS = "#add_spf_to_domains";

    const SAVE_SETTINGS_BTN_XPATH = "//input[@id='submit']";
    const SAVE_SETTINGS_BTN_CSS = "#submit";

    const SUCCESS_ALERT_XPATH = "//div[@class='alert alert-success']";
    const SUCCESS_ALERT_CSS = ".alert.alert-success";

    const DANGER_ALERT_XPATH ="//div[@class='alert alert-error alert-danger']";
    const DANGER_ALERT_CSS = ".alert.alert-error.alert-danger";

    const OPT_ERROR_MESSAGE_CONTAINER   = "//div[@class='error control-group']";
}
