<?php

use Step\Acceptance\CommonSteps;

class C00PsfInstallCest
{
    public function verifyCpanelPsfAddonIsInstalled(CommonSteps $I)
    {
        $I->amGoingTo('check psf is installed ');
        $I->login();
        $I->checkPsfIsPresent();
    }
}
