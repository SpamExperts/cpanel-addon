<?php

use Page\DomainListPage;
use Step\Acceptance\CommonSteps;

class C03DomainListCest
{
    public function _before(CommonSteps $I)
    {
        $I->loginAsRoot();
    }

    public function _after(CommonSteps $I)
    {
        $I->removeCreatedAccounts();
    }

    public function _failed(CommonSteps $I)
    {
        $this->_after($I);
    }

    public function verifyDomainListAsRoot(CommonSteps $I)
    {
        $I->checkPsfIsPresent();
        $domain = $I->generateRandomDomainName();
        $I->createNewAccount(['domain' => $domain]);
        $I->checkDomainList($domain);
    }

    public function verifyDomainListAsReseller(CommonSteps $I)
    {
        $account = $I->createNewAccount([
            'reseller' => true
        ]);
        $secondAccount = $I->createNewAccount([
            'reseller' => true
        ]);
        $I->login($secondAccount['username'], $secondAccount['password']);
        $secondAccountDomain = $I->createNewAccount([
            'ui' => true
        ]);

        $I->logout();

        $I->login($account['username'], $account['password']);
        $I->checkDomainList($account['domain']);

        $I->dontSee($secondAccount['domain'], DomainListPage::DOMAIN_TABLE);
        $I->dontSee($secondAccountDomain['domain'], DomainListPage::DOMAIN_TABLE);

        $I->logout();
        $I->loginAsRoot();
    }

    public function verifyDomainListAsCustomer(CommonSteps $I)
    {
        $account = $I->createNewAccount();
        $nonCustomerDomains = array(
            $I->createNewAccount(),
            $I->createNewAccount(),
        );
        $I->loginAsClient($account['username'], $account['password']);
        $I->checkDomainListAsClient($account['domain']);
        $I->dontSeeInDomainTable($nonCustomerDomains[0]['domain']);
        $I->dontSeeInDomainTable($nonCustomerDomains[1]['domain']);
    }

    public function verifyDomainToggleProtection(CommonSteps $I)
    {
        $account = $I->createNewAccount();
        $domain = $account['domain'];

        $I->checkDomainIsPresentInFilter($domain);
        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of $domain has been changed to unprotected", 60);
        $I->checkDomainIsNotPresentInFilter($domain);
        $I->assertDomainNotExistsInSpampanel($domain);

        $I->click(DomainListPage::TOGGLE_PROTECTION_LINK);
        $I->waitForText("The protection status of $domain has been changed to protected", 60);
        $I->checkDomainIsPresentInFilter($domain);
        $I->assertDomainExistsInSpampanel($domain);
    }

    public function verifyDomainLoginAsRoot(CommonSteps $I)
    {
        $account = $I->createNewAccount();
        $I->searchDomainList($account['domain']);
        $I->loginOnSpampanel($account['domain']);
        $I->loginAsRoot();
    }

    public function verifyDomainLoginAsClient(CommonSteps $I)
    {
        $account = $I->createNewAccount();
        $domain = $account['domain'];
        $I->loginAsClient($account['username'], $account['password']);
        $I->checkDomainListAsClient($domain);
        $I->loginOnSpampanel($domain);
        $I->loginAsRoot();
    }
}
