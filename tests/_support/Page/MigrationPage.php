<?php

namespace Page;

class MigrationPage
{
	const MIGRATE_THUMBNAIL = 'html/body/div[1]/div/ul/li[5]/div';
    const TITLE = "Migration";
    const TITLE_XPATH = "//h3[contains(.,'Migration')]";
    const DESCRIPTION_A = "On this page you can migrate to a different admin/reseller in the spamfilter.";
    const DESCRIPTION_B = "During migration, the domains will be assigned to the new user (given the credentials for the new user are correct) and the configuration of the addon will be switched to the new user.";

    const CURRENT_USERNAME_XPATH = "//input[@id='current_user']";
    const CURRENT_USERNAME_CSS   = "#current_user";

    const NEW_USERNAME_XPATH = "//input[@id='new_user']";
    const NEW_USERNAME_CSS   = "#new_user";

    const NEW_PASSWORD_XPATH = "//input[@type='password']";
    const NEW_PASSWORD_CSS   = "#new_password";

    const CONFIRM_INPUT_XPATH = "//input[@id='confirmation']";
    const CONFIRM_INPUT_CSS   = "#confirmation";

    const MIGRATE_BTN_XPATH = "//input[@id='submit']";
    const MIGRATE_BTN_CSS   = "#submit";
}
