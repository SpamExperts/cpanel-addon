<?php

namespace Page;

class CpanelWHMPage
{
    // Main Page
    const LOGOUT_BTN = "//a[@href='/logout/?locale=en']";

    const ACCOUNT_SUBMIT_BTN = '#submit';

    const SEARCH_BAR_XPATH = "//input[@id='quickJump']";
    const SEARCH_BAR_CSS = "#quickJump";

    const TOP_FRAME_XPATH = "//*[@id='topFrame']";
    const TOP_FRAME_CSS = "#topFrame";
    const TOP_FRAME_NAME = "topFrame";

    const COMMANDER_FRAME_XPATH = "//*[@id='commander']";
    const COMMANDER_FRAME_CSS = "#commander";
    const COMMANDER_FRAME_NAME = "commander";

    const MAIN_FRAME_XPATH = "//*[@id='mainFrame']";
    const MAIN_FRAME_CSS = "#mainFrame";
    const MAIN_FRAME_NAME = "mainFrame";

    // Create new account page
    const DOMAIN_FIELD_XPATH = "//*[@id='domain']";
    const DOMAIN_FIELD_CSS = "#domain";

    const USERNAME_FIELD_XPATH = "//*[@id='username']";
    const USERNAME_FIELD_CSS = "#username";

    const PASSWORD_FIELD_XPATH = "//*[@id='password']";
    const PASSWORD_FIELD_CSS = "#password";

    const RE_PASSWORD_FIELD_XPATH = "//*[@id='password2']";
    const RE_PASSWORD_FIELD_CSS = "#password2";

    const EMAIL_FIELD_XPATH = "//*[@id='contactemail']";
    const EMAIL_FIELD_CSS = "#contactemail";

    const CHOOSE_PKG_DROP_DOWN_XPATH = "//*[@id='pkgselect']";
    const CHOOSE_PKG_DROP_DOWN_CSS = "#pkgselect";

    const MAKE_RESELLER_OPT_XPATH = "//*[@id='resell']";
    const MAKE_RESELLER_OPT_CSS = "#resell";

    const CREATE_ACCOUNT_BTN_XPATH = "#//*[@id='submit']";
    const CREATE_ACCOUNT_BTN_CSS = "#submit";

    // Edit Reseller Nameservers and Privileges page
    const RESELLER_LIST_CSS = "#pageContainer>form>select";
    const RESELLER_LIST_XPATH = ".//*[@id='pageContainer']/form/select";

    const RESELLER_SUBMIT_BTN_XPATH = ".//*[@id='pageContainer']/form/input";
    const RESELLER_SUBMIT_BTN_CSS = "#pageContainer > form > input";

    const RESELLER_ACCESS_ALL_OPT_CSS = "#acl_group_everything > li > input[type='checkbox']";
    const RESELLER_ACCESS_ALL_OPT_XPATH = "//*[@id='acl_group_everything']/li/input";

    // Add a package page
    const PACKAGE_NAME_FIELD_CSS = "#yui-gen0 > div:nth-child(1) > fieldset > div > div > div.propertyValue > input";
    const PACKAGE_NAME_FIELD_XPATH = "//*[@id='yui-gen0']/div[1]/fieldset/div/div/div[2]/input";

    //Feature
    const DELETE_FEATURE_XPATH = "//button[@id='btnDeleteFeatureList']";
    const DELETE_FEATURE_CSS = "#btnDeleteFeatureList";

    const SAVE_FEATURE_LIST_XPATH = "//button[@cp-action='save(featureList)']";
    const SAVE_FEATURE_LIST_CSS = "#btnSaveFeatureList";

    const ADD_FEATURE_LIST_XPATH = "//button[@cp-action='add(newFeatureList)']";
    const ADD_FEATURE_LIST_CSS = "#btnAddFeatureList";

    const SPAMEXPERTS_OPTION_XPATH = "//input[@id='chk_prospamfilter']";
    const SPAMEXPERTS_OPTION_CSS = "#chk_prospamfilter";

    const DROP_DOWN_LIST_XPATH = "//select[@id='ddlSelectedFeatureList']";
    const DROP_DOWN_LIST_XPATH_CSS = "#ddlSelectedFeatureList";

    const ADD_NEW_FEATURE_XPATH = "//input[@id='txtNewFeatureList']";
    const ADD_NEW_FEATURE_XPATH_CSS ="#txtNewFeatureList";

    const SUBDOMAIN_MANAGER_OPTION_XPATH = "//input[@id='chk_subdomains']";
    const SUBDOMAIN_MANAGER_OPTION_CSS = "#chk_subdomains";

    const ADDON_DOMAIN_MANAGER_OPTION_XPATH = "//input[@id='chk_addondomains']";
    const ADDON_DOMAIN_MANAGER_OPTION_CSS = "#chk_addondomains";

    const PARKED_DOMAIN_MANAGER_OPTION_XPATH = "//input[@id='chk_parkeddomains']";
    const PARKED_DOMAIN_MANAGER_OPTION_CSS = "#chk_parkeddomains";

    //Remove accounts
    const CHECK_BOX_XPATH = "//input[@id='checkbox-confirm-deletion']";
    const CHECK_BOX_CSS = "#checkbox-confirm-deletion";

    const CONFIRM_REMOVE_XPATH = "//button[@cp-action='confirming_accounts_removal = true']";
    const CONFIRM_REMOVE_CSS = "#action-remove-selected";

    const PERMANENTLY_DELETE_XPATH = "//button[contains(.,'Yes, permanently remove the selected accounts.')]";
    const PERMANENTLY_DELETE_CSS = ".btn.btn-primary";

    const SELECT_DESIRED_FEATURE_XPATH = "//select[contains(@name,'featurelist')]";
    const SELECT_DESIRED_FEATURE_CSS = "#featurelist > select";

    const REQUEST_MULTI_DETELE_ACC_XPATH = "//button[@cp-action='request_multi_delete_confirmation()']";
    const REQUEST_MULTI_DETELE_ACC_CSS = "#action-remove-selected";

    const SELECT_ALL_VISIBLE_ACCOUNTS_XPATH = "//input[@id='select_all_visable_accts']";
    const SELECT_ALL_VISIBLE_ACCOUNTS_CSS = "#select_all_visable_accts";
}
