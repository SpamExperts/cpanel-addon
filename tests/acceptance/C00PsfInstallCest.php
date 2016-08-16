<?php

use Step\Acceptance\CommonSteps;

class C00PsfInstallCest
{
    /**
     * Verify if Professional Spam Filter plugin is installed
     */
    public function verifyCpanelPsfAddonIsInstalled(CommonSteps $I)
    {
        $I->amGoingTo('check psf is installed ');

        // Login as root
        $I->login();

        // Check if Professional Spam Filter is installed
        $I->checkPsfIsPresent();
    }
}
