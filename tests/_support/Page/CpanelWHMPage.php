<?php

namespace Page;

class CpanelWHMPage
{
    // Main Page
    const LOGOUT_BTN_XPATH = "body/div/div[2]/div/ul/li[4]/a";
    const LOGOUT_BTN_CSS = "body > div > div.navigationContainer > div > ul > li:nth-child(4) > a";

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
    const RESELLER_LIST_XPATH = ".//*[@id='pageContainer']/form/select";
    const RESELLER_LIST_CSS = "#pageContainer>form>select";

    const RESELLER_SUBMIT_BTN_XPATH = ".//*[@id='pageContainer']/form/input";
    const RESELLER_SUBMIT_BTN_CSS = "#pageContainer > form > input";

    const RESELLER_ACCESS_ALL_OPT_XPATH = "//*[@id='acl_group_everything']/li/input";
    const RESELLER_ACCESS_ALL_OPT_CSS = "#acl_group_everything > li > input[type='checkbox']";

    // Add a package page
    const PACKAGE_NAME_FIELD_XPATH = "//*[@id='yui-gen0']/div[1]/fieldset/div/div/div[2]/input";
    const PACKAGE_NAME_FIELD_CSS = "#yui-gen0 > div:nth-child(1) > fieldset > div > div > div.propertyValue > input";
}
