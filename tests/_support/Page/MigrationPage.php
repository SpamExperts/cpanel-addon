<?php

namespace Page;

class MigrationPage
{
    const TITLE = "Migration";
    const DESCRIPTION_A = "On this page you can migrate to a different admin/reseller in the spamfilter.";
    const DESCRIPTION_B = "During migration, the domains will be assigned to the new user (given the credentials for the new user are correct) and the configuration of the addon will be switched to the new user.";

    const CURRENT_USERNAME = "//input[@id='current_user']";
    const NEW_USERNAME     = "//input[@id='new_user']";
    const NEW_PASSWORD     = "//input[@type='password']";
    const CONFIRM_INPUT    = "//input[@id='confirmation']";

    const MIGRATE_BTN = "//input[@id='submit']";
}
