<?php

namespace Page;

class DomainListPage
{
    const TITLE = "List Domains";
    const DESCRIPTION = "This page shows you a list of all domains owned by you and its respective status in the spamfilter.";

    const SEARCH_FIELD_XPATH = "//*[@id='searchInput']";
    const SEARCH_FIELD_CSS = "#searchInput";

    const SEARCH_BTN_XPATH = "//*[@id='searchSubmit']";
    const SEARCH_BTN_CSS = "#searchSubmit";


    const RESET_BTN_XPATH = "//*[@id='searchReset']";
    const RESET_BTN_CSS = "#searchReset";


    const CHECK_STATUS_FOR_ALL_DOMAIN    = "//button[@id='checkAllDomains']";


    const TOOGLE_PROTECTION_FOR_SELECTED = "//button[@id='toggleSelected']";
    const ITEMS_PER_PAGE_INPUT           = "//input[@id='itemsPerPage']";
    const CHANGE_BTN                     = "//button[@id='changeItems']";

    const DOMAIN_TABLE_XPATH = "//*[@id='domainoverview']";
    const DOMAIN_TABLE_CSS = "#domainoverview";

    const TYPE_COLUMN_FROM_FIRST_ROW     = "//*[@id=\"domainoverview\"]/tbody/tr[1]/td[3]";

    const CHECK_STATUS_LINK         = "span.pstatus a";
    const TOGGLE_PROTECTION_LINK    = "//a[contains(.,'Toggle Protection')]";
    const LOGIN_LINK                = "//a[contains(.,'Login')]";

    // Status messages
    const STATUS_DOMAIN_IS_PRESENT_IN_THE_FILTER = 'This domain is present in the filter.';
    const STATUS_DOMAIN_IS_NOT_PRESENT_IN_THE_FILTER = 'This domain is not present in the filter.';
}
