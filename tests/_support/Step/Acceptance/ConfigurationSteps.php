<?php

namespace Step\Acceptance;

use Pages\ConfigurationPage;
use Pages\ProfessionalSpamFilterPage;

class ConfigurationSteps extends CommonSteps
{
    public function verifyPageLayout()
    {
        $I = $this;
        $I->amGoingTo("\n\n --- Check configuration page layout --- \n");
        $I->see("Configuration", "//h3[contains(.,'Configuration')]");
        $I->see('You can hover over the options to see more detailed information about what they do.');

        // Setting up configuration
        $I->seeElement(ProfessionalSpamFilterPage::CONFIGURATION_LINK);
        $I->seeElement(ProfessionalSpamFilterPage::BRANDING_LINK);
        $I->seeElement(ProfessionalSpamFilterPage::DOMAIN_LIST_LINK);
        $I->seeElement(ProfessionalSpamFilterPage::BRANDING_LINK);
        $I->seeElement(ProfessionalSpamFilterPage::MIGRATION_LINK);
        $I->seeElement(ProfessionalSpamFilterPage::UPDATE_LINK);
        $I->seeElement(ProfessionalSpamFilterPage::SUPPORT_LINK);
        $I->see('AntiSpam API URL');
        $I->seeElement(ConfigurationPage::ANTISPAM_API_URL);
        $I->see('API hostname');
        $I->seeElement(ConfigurationPage::API_HOSTNAME);
        $I->see('API username');
        $I->seeElement(ConfigurationPage::API_USERNAME);
        $I->see('API password');
        $I->seeElement(ConfigurationPage::API_PASSWORD);
        $I->see('Primary MX');
        $I->seeElement(ConfigurationPage::MX_PRIMARY);
        $I->see('Secondary MX');
        $I->seeElement(ConfigurationPage::MX_SECONDARY);
        $I->see('Tertiary MX');
        $I->seeElement(ConfigurationPage::MX_TERTIARY);
        $I->see('Quaternary MX');
        $I->seeElement(ConfigurationPage::MX_QUATERNARY);
        $I->see('SPF Record');
        $I->seeElement(ConfigurationPage::SPF_RECORD);
        $I->see('TTL to use for MX records');
        $I->seeElement(ConfigurationPage::TTL_FOR_MX);
        $I->see('Language');
        $I->seeElement(ConfigurationPage::LANGUAGE_DROP_DOWN);

        // selecting options
        $I->see('Enable SSL for API requests to the spamfilter and Cpanel');
        $I->seeElement(ConfigurationPage::ENABLE_SSL_FOR_API_OPT);
        $I->see('Enable automatic updates');
        $I->seeElement(ConfigurationPage::ENABLE_AUTOMATIC_UPDATES_OPT);
        $I->see('Automatically add domains to the SpamFilter');
        $I->seeElement(ConfigurationPage::AUTOMATICALLY_ADD_DOMAINS_OPT);
        $I->see('Automatically delete domains from the SpamFilter');
        $I->seeElement(ConfigurationPage::AUTOMATICALLY_DELETE_DOMAINS_OPT);
        $I->see('Automatically change the MX records for domains');
        $I->seeElement(ConfigurationPage::AUTOMATICALLY_CHANGE_MX_OPT);
        $I->see('Configure the email address for this domain');
        $I->seeElement(ConfigurationPage::CONFIGURE_EMAIL_ADDRESS_OPT);
        $I->see('Process addon-, parked and subdomains');
        $I->seeElement(ConfigurationPage::PROCESS_ADDON_CPANEL_OPT);
        $I->see('Add addon-, parked and subdomains as an alias instead of a normal domain.');
        $I->seeElement(ConfigurationPage::ADD_ADDON_AS_ALIAS_CPANEL_OPT);
        $I->see('Use existing MX records as routes in the spamfilter.');
        $I->seeElement(ConfigurationPage::USE_EXISTING_MX_OPT);
        $I->see('Do not protect remote domains');
        $I->seeElement(ConfigurationPage::DO_NOT_PROTECT_REMOTE_DOMAINS_OPT);
        $I->see('Redirect back to Cpanel upon logout');
        $I->seeElement(ConfigurationPage::REDIRECT_BACK_TO_CPANEL_OPT);
        $I->see('Add the domain to the spamfilter during login if it does not exist');
        $I->seeElement(ConfigurationPage::ADD_DOMAIN_DURING_LOGIN_OPT);
        $I->see('Force changing route & MX records, even if the domain exist');
        $I->seeElement(ConfigurationPage::FORCE_CHANGE_MX_ROUTE_OPT);
        $I->see('Change email routing setting "auto" to "local" in bulk protect.');
        $I->seeElement(ConfigurationPage::CHANGE_EMAIL_ROUTING_OPT);
        $I->see('Add/Remove a domain when the email routing is changed in Cpanel');
        $I->seeElement(ConfigurationPage::ADD_REMOVE_DOMAIN);
        $I->see('Disable addon in cPanel for reseller accounts.');
        $I->seeElement(ConfigurationPage::DISABLE_ADDON_IN_CPANEL);
        $I->see('Use IP as destination route instead of domain');
        $I->seeElement(ConfigurationPage::USE_IP_AS_DESTINATION_OPT);
        $I->see('Set SPF record for domains');
        $I->seeElement(ConfigurationPage::SET_SPF_RECORD);
        // 'Save Settings' button
        $I->seeElement(ConfigurationPage::SAVE_SETTINGS_BTN);
    }

    public function setFieldApiUrl($string)
    {
        $this->fillField(ConfigurationPage::ANTISPAM_API_URL, $string);
    }

    public function setFieldApiHostname($string)
    {
        $this->fillField(ConfigurationPage::API_HOSTNAME, $string);
    }

    public function setFieldApiUsernameIfEmpty($string)
    {
        $I = $this;

        $value = $I->grabValueFrom(ConfigurationPage::API_USERNAME);

        if (! $value) {
            $I->fillField(ConfigurationPage::API_USERNAME, $string);
        }
    }

    public function setFieldApiPassword($string)
    {
        $this->fillField(ConfigurationPage::API_PASSWORD, $string);
    }

    public function setFieldPrimaryMX($string)
    {
        $this->fillField(ConfigurationPage::MX_PRIMARY, $string);
    }

    public function getMxFields()
    {
        $mxRecords = [];
        $mxRecords[] = $this->grabValueFrom(ConfigurationPage::MX_PRIMARY);
        $mxRecords[] = $this->grabValueFrom(ConfigurationPage::MX_SECONDARY);
        $mxRecords[] = $this->grabValueFrom(ConfigurationPage::MX_TERTIARY);
        $mxRecords[] = $this->grabValueFrom(ConfigurationPage::MX_QUATERNARY);

        return array_filter($mxRecords);
    }

    public function setFieldSpfRecord($string)
    {
        $this->fillField(ConfigurationPage::SPF_RECORD, $string);
    }

    public function submitSettingForm()
    {
        $this->click('Save Settings');
    }

    public function seeSubmissionIsSuccessful()
    {
        $this->see('The settings have been saved.');
    }
}
