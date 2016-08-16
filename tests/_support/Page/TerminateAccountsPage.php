<?php

namespace Page;

class TerminateAccountsPage
{
    const DESTROY_ACCOUNTS_INPUT_XPATH = '//*[@id="masterContainer"]/form/input[@name="verify"]';
    const DESTROY_ACCOUNTS_INPUT_CSS   = '#masterContainer>form>input';

    const DESTROY_ACCOUNTS_BTTN_XPATH = "//input[@type='submit']";
    const DESTROY_ACCOUNTS_BTTN_CSS   = ".btn-primary";
}
