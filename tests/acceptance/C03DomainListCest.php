<?php

use Page\DomainListPage;
use Step\Acceptance\CommonSteps;
use Codeception\Util\Locator;

class C03DomainListCest
{
    /**
     * Function called before each test
     */
    public function _before(CommonSteps $I)
    {
        $I->loginAsRoot();
        $I->createDefaultPackage();
    }

    /**
     * Function called after each test
     */
    public function _after(CommonSteps $I)
    {
        $I->removeCreatedAccounts();
    }

    /**
     * Function called after a test failed
     */
    public function _failed(CommonSteps $I)
    {
        $this->_after($I);
    }

    /**
     * Verify if a new account is added it will be present in plugin domain list
     */
    public function verifyDomainListAsRoot(CommonSteps $I)
    {
        // Check if plugin is installed
        $I->checkPsfIsPresent();

        // Create a new client account
        $account = $I->createNewAccount();

        // Check if account domain is present in plugin domain list
        $I->searchDomainList($account['domain']);
    }

    /**
     * Verify domain list as a reseller
     */
    public function verifyDomainListAsReseller(CommonSteps $I)
    {
        // Create new reseller account
        $account = $I->createNewAccount([
            'reseller' => true
        ]);

        // Create a second reseller account
        $secondAccount = $I->createNewAccount([
            'reseller' => true
        ]);

        // Login with the second reseller account
        $I->login($secondAccount['username'], $secondAccount['password']);

        // Create a new client account from the second reseller account
        $secondAccountDomain = $I->createNewAccount([
            'ui' => true
        ]);

        // Logout from the reseller account
        $I->logout();

        // Login with the first reseller account
        $I->login($account['username'], $account['password']);

        // Check if domain is present in plugin domain list
        $I->searchDomainList($account['domain']);

        // Check to see if the other accounts related domains are not present in plugin domain list
        $I->searchDomainNotinList($secondAccount['domain']);
        $I->searchDomainNotinList($secondAccountDomain['domain']);
    }

    /**
     * Verify domain list as a customer
     */
    public function verifyDomainListAsCustomer(CommonSteps $I)
    {
        // Create a new client account
        $account = $I->createNewAccount();

        // Create two more client accounts
        $nonCustomerDomains = array(
            $I->createNewAccount(),
            $I->createNewAccount(),
        );

        // Login with the first client account
        $I->loginAsClient($account['username'], $account['password']);

        // Check if domain related to account is present in plugin domain list
        $I->checkDomainListAsClient($account['domain']);

        // Check if the other accounts domains are not present in plugin domain list
        $I->dontSeeInDomainTable($nonCustomerDomains[0]['domain']);
        $I->dontSeeInDomainTable($nonCustomerDomains[1]['domain']);
    }

    /**
     * Verify toggle protection on domain list
     */
    public function verifyDomainToggleProtection(CommonSteps $I)
    {
        // Create new client account
        $account = $I->createNewAccount();
        $domain = $account['domain'];

        // Check if domain related to client account is present in filter
        $I->checkDomainIsPresentInFilter($domain);

        // Toggle protection on that domain
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);

        // Wait for toggle protection to finish
        $I->waitForText("The protection status of $domain has been changed to unprotected", 60);

        // Check if domain is not present in filter
        $I->checkDomainIsNotPresentInFilter($domain);

        // Check if domain is no longer present in spampanel
        $I->assertDomainNotExistsInSpampanel($domain);

        // Toggle protection o that domain
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);

        // Wait for toggle protection to finish
        $I->waitForText("The protection status of $domain has been changed to protected", 60);

        // Check if the domain is present in filter
        $I->checkDomainIsPresentInFilter($domain);

        // Check if domain is no longer present in spampanel
        $I->assertDomainExistsInSpampanel($domain);
    }

    /**
     * Verify domain login on spampanel
     */
    public function verifyDomainLoginAsRoot(CommonSteps $I)
    {
        // Create new client account
        $account = $I->createNewAccount();

        // Check if the domain related to client account is present in list
        $I->searchDomainList($account['domain']);

        // Try to login in spampanel using the account
        $I->loginOnSpampanel($account['domain']);
    }

    /**
     * Verify domain login as client
     */
    public function verifyDomainLoginAsClient(CommonSteps $I)
    {
        // Create new client account
        $account = $I->createNewAccount();

        // Login as client
        $I->loginAsClient($account['username'], $account['password']);

        // Check if domain related to account is present in plugin domain list
        $I->checkDomainListAsClient($account['domain']);

        // Try to login in spampanel using the account
        $I->loginOnSpampanel($account['domain']);
    }
}
