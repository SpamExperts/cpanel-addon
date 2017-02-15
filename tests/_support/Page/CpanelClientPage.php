<?php

namespace Page;

class CpanelClientPage
{
    const HOME_MENU_LINK_XPATH = "//a[@id='lnkHeaderHome']";
    const HOME_MENU_LINK_CSS = '#lnkHeaderHome';

    const HOME_MENU_LINK_V52 = '#lnkHeaderHome';

    const CLIENT_LOGOUT_BTN_XPATH = "//*[@id='lnkHeaderLogout']";
    const CLIENT_LOGOUT_BTN_CSS = "#lnkHeaderLogout";

    const DASHBOARD_BTN_XPATH = "//*[@id='lnkDashboard']";
    const DASHBOARD_BTN_CSS = "#lnkDashboard";

    const SEARCH_BAR_CONTAINER_XPATH = "//*[@id='jump-search']";
    const SEARCH_BAR_CONTAINER_CSS = "#jump-search";

    const SEARCH_BAR_XPATH = "//*[@id='quickjump']";
    const SEARCH_BAR_CSS = "#quickjump";

    const PARKED_DOMAINS_XPATH = "//*[@id='item_parkeddomains']";
    const PARKED_DOMAINS_CSS = "#item_parkeddomains";

    // Addon Domains page
    const NEW_DOMAIN_NAME_FIELD_XPATH = "//*[@id='domain']";
    const NEW_DOMAIN_NAME_FIELD_CSS = "#domain";

    const SUBDOMAIN_FIELD_XPATH = "//*[@id='subdomain']";
    const SUBDOMAIN_FIELD_CSS = "#subdomain";

    const DOCUMENT_ROOT_FIELD_XPATH = "//*[@id='dir']";
    const DOCUMENT_ROOT_FIELD_CSS = "#dir";

    const ADDON_DOMAIN_SEARCH_BAR_XPATH = "//*[@id='searchregex']";
    const ADDON_DOMAIN_SEARCH_BAR_CSS = "#searchregex";

    const ADDON_DOMAIN_SEARCH_BTN_XPATH = "//*[@id='search']";
    const ADDON_DOMAIN_SEARCH_BTN_CSS = "#search";

    const ADDON_DOMAIN_CONFIRM_REMOVE_BTN_XPATH = "//*[@id='btnRemove']";
    const ADDON_DOMAIN_CONFIRM_REMOVE_BTN_CSS = "#btnRemove";

    // Subdomains page

    const ADD_SUBDOMAIN_FIELD_XPATH = "//*[@id='domain']";
    const ADD_SUBDOMAIN_FIELD_CSS = "#domain";

    const ADD_SUBDOMAIN_ROOT_DOMAIN_FIELD_XPATH = "//*[@id='rootdomain']";
    const ADD_SUBDOMAIN_ROOT_DOMAIN_FIELD_CSS = "#rootdomain";

    const SUBDOMAIN_SEARCH_BAR_XPATH = "//*[@id='searchregex']";
    const SUBDOMAIN_SEARCH_BAR_CSS = "#searchregex";

    const SUBDOMAIN_SEARCH_BTN_XPATH = "//*[@id='search']";
    const SUBDOMAIN_SEARCH_BTN_CSS = "#search";

    const DELETE_SUBDOMAIN_BTN_XPATH = "//*[@id='deleteSubdomain']";
    const DELETE_SUBDOMAIN_BTN_CSS = "#deleteSubdomain";


    // Aliases page
    const ALIAS_DOMAIN_FIELD_XPATH = "//*[@id='domain']";
    const ALIAS_DOMAIN_FIELD_CSS = "#domain";

    const ALIAS_DOMAIN_SEARCH_BAR_XPATH = "//*[@id='searchregex']";
    const ALIAS_DOMAIN_SEARCH_BAR_CSS = "#searchregex";

    const ALIAS_DOMAIN_SEARCH_BTN_XPATH = "//*[@id='btnGo']";
    const ALIAS_DOMAIN_SEARCH_BTN_CSS = "#btnGo";

    const DELETE_ALIAS_BTN_XPATH = "//*[@id='btnRemove']";
    const DELETE_ALIAS_BTN_CSS = "#btnRemove";

    // MX Entry page
    const LOCAL_MAIL_EXCHANGER_OPT_XPATH = ".//*[@id='mxcheck_local']";
    const LOCAL_MAIL_EXCHANGER_OPT_CSS = "#mxcheck_local";

    const BACKUP_EMAIL_EXCHANGER_OPT_XPATH = ".//*[@id='mxcheck_secondary']";
    const BACKUP_EMAIL_EXCHANGER_OPT_CSS = "#mxcheck_secondary";

    const REMOTE_EMAIL_EXCHANGER_OPT_XPATH = ".//*[@id='mxcheck_remote']";
    const REMOTE_EMAIL_EXCHANGER_OPT_CSS = "#mxcheck_remote";

}
