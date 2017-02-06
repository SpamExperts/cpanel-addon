<?php

class SpamFilter_ProtectionManager
{
    /**
     * @var SpamFilter_Hooks
     */
    private $hook;

    /**
     * @var stdClass
     */
    private $config;

    /**
     * @var IDNA_Convert
     */
    private $idn;
    /**
     * @var Zend_Translate
     */
    private $translator;

    /**
     * @var SpamFilter_PanelSupport_Cpanel
     */
    private $panel;

    /**
     * @var SpamFilter_ResellerAPI
     */
    private $api;

    /**
     * @var SpamFilter_Logger
     */
    private $logger;

    public function __construct()
    {
        $this->hook = new SpamFilter_Hooks;
        $this->config = Zend_Registry::get('general_config');
        $this->idn = new IDNA_Convert;
        $this->translator = Zend_Registry::get('translator');
        $this->panel = new SpamFilter_PanelSupport_Cpanel();
        $this->api 	= new SpamFilter_ResellerAPI;
        $this->logger = Zend_Registry::get('logger');
    }

    /**
     * @param string $domain
     * @param string $ownerDomain
     * @param string $domainType
     * @param string $ownerUser
     *
     * @return array(string status, string message)
     */
    public function toggleProtection($domain, $ownerDomain, $domainType, $ownerUser)
    {
        $this->log("Toggling protection for $domain");
        $inFilter = $this->panel->isInFilter($domain, $ownerDomain);

        if ($inFilter) {
            $response = $this->unprotect($domain, $ownerDomain, $domainType);
            $response = $this->formatToggleProtectionResponse($response, "unprotected", $domain);
        } else {
            $response = $this->protect($domain, $ownerDomain, $domainType, $ownerUser);
            $response = $this->formatToggleProtectionResponse($response, "protected", $domain);
        }

        return $response;
    }

    /**
     * @param string $domain
     * @param string $ownerDomain
     * @param string $domainType
     *
     * @return array(boolean status , string rawresult, string reason
     */
    public function unprotect($domain, $ownerDomain, $domainType)
    {
        $this->log("Unprotecting $domain");
        $isSecondaryDomain = in_array($domainType, array('subdomain', 'parked', 'addon', 'alias'));
        $addAddonAsAliasOption = 0 < $this->config->add_extra_alias;

        // PA = "process addon domains" option
        // AAA = "add addon as alias" option
        // case: is secondary domain and not PA, throw error
        if ($isSecondaryDomain && !(0 < $this->config->handle_extra_domains)) {
            return $this->createUnprotectResponse(false, SpamFilter_Hooks::SKIP_PROCESS_OF_ADDON_DOMAINS_DISABLED);
        }

        // owner domain param not sent
        if ($isSecondaryDomain && ! $ownerDomain) {
            return $this->createUnprotectResponse(false, SpamFilter_Hooks::SKIP_OWNER_DOMAIN_NOT_FOUND);
        }

        // case: no ownership, throw error
        $domainToValidate = $ownerDomain ? $ownerDomain : $domain;
        if (!$this->panel->validateOwnership($domainToValidate)) {
            return $this->createUnprotectResponse(false, SpamFilter_Hooks::SKIP_OWNER_VALIDATION_FAIL);
        }

        // case: is sec domain, added as alias, now without AAA, throw error that is already in filter as alias
        if ($isSecondaryDomain && ! $addAddonAsAliasOption && $this->isAliasInSpampanel($domain, $ownerDomain)) {
            return $this->createUnprotectResponse(false, SpamFilter_Hooks::SKIP_ADDED_AS_ALIAS_NOT_DOMAIN);
        }

        // case: is sec domain, added as normal domain, now with AAA, throw error that is already in filter as normal domain
        if ($isSecondaryDomain && $addAddonAsAliasOption && SpamFilter_Domain::exists($domain) && ! $this->isAliasInSpampanel($domain, $ownerDomain)) {
            return $this->createUnprotectResponse(false, SpamFilter_Hooks::SKIP_ADDED_AS_DOMAIN_NOT_ALIAS);
        }

        if ($isSecondaryDomain && $this->isAliasInSpampanel($domain, $ownerDomain)) {
            $status = $this->hook->DelAlias($ownerDomain, $domain);
        } else {
            $status = $this->hook->DelDomain($domain, true, true); // force removal, reset DNS zone for manual removes
        }

        // $status: (boolean) status , (string) reason, (string) rawresult Hooks constant

        $this->log("Hook delete result:");
        $this->log($status);

        if (!isset($status['status'])) {
            $status['status'] = false;
            $status['rawresult'] = SpamFilter_Hooks::SKIP_APIFAIL;
        }

        return $status;
    }

    /**
     * @param boolean $status
     * @param string $rawResult
     *
     * @return array
     */
    private function createUnprotectResponse($status, $rawResult)
    {
        $response =  array(
            'status' => $status,
            'rawresult' => $rawResult
        );

        $this->log("Unprotect response:");
        $this->log($response);

        return $response;
    }

    private function createProtectResponse($domain, $reason, $rawResult = null, $reasonStatus = "error")
    {
        $response = $this->panel->createBulkProtectResponse($domain, $reason, $reasonStatus, $rawResult);
        $response['status'] = !empty($status['reason_status']) && 'ok' == $status['reason_status'];

        $this->log("Protect response:");
        $this->log($response);

        return $response;
    }

    public function protect($domain, $ownerDomain, $domainType, $ownerUser = null)
    {
        $this->log("Protecting $domain");
        $isSecondaryDomain = in_array($domainType, array('subdomain', 'parked', 'addon', 'alias'));
        $addAddonAsAliasOption = 0 < $this->config->add_extra_alias;

        // PA = "process addon domains" option
        // AAA = "add addon as alias" option
        // case: is secondary domain and not PA, throw error
        if ($isSecondaryDomain && !(0 < $this->config->handle_extra_domains)) {
            return $this->createProtectResponse($domain, "Skip: Processing of addon domains disabled", SpamFilter_Hooks::SKIP_PROCESS_OF_ADDON_DOMAINS_DISABLED);
        }

        // owner domain param not sent
        if ($isSecondaryDomain && ! $ownerDomain) {
            return $this->createProtectResponse($domain, "Skip: Owner domain not found", SpamFilter_Hooks::SKIP_OWNER_DOMAIN_NOT_FOUND);
        }

        // case: no ownership, throw error
        $domainToValidate = $ownerDomain ? $ownerDomain : $domain;
        if (!$this->panel->validateOwnership($domainToValidate)) {
            return $this->createProtectResponse($domain, "Skip: Validation fail", SpamFilter_Hooks::SKIP_OWNER_VALIDATION_FAIL);
        }

        // case: is sec domain, added as alias, now without AAA, throw error that is already in filter as alias
        if ($isSecondaryDomain && ! $addAddonAsAliasOption && $this->isAliasInSpampanel($domain, $ownerDomain)) {
            return $this->createProtectResponse($domain, "Skip: Added as alias instead of domain", SpamFilter_Hooks::SKIP_ADDED_AS_ALIAS_NOT_DOMAIN);
        }

        // case: is sec domain, added as normal domain, now with AAA, throw error that is already in filter as normal domain
        if ($isSecondaryDomain && $addAddonAsAliasOption && SpamFilter_Domain::exists($domain) && ! $this->isAliasInSpampanel($domain, $ownerDomain)) {
            return $this->createProtectResponse($domain, "Skip: Added as domain instead of alias", SpamFilter_Hooks::SKIP_ADDED_AS_DOMAIN_NOT_ALIAS);
        }

        // Add to filter
        $status = $this->panel->bulkProtect(array(
            'domain' => $domain,
            'type' => $domainType,
            'owner_domain' => $ownerDomain,
            'owner_user' => !empty($ownerUser) ? $ownerUser : $this->panel->getDomainUser($domain)
        ));

        // $status: string $domain, array $counts, string $reason, string $reason_status, string $raw_result, string $time_start, string $time_execute

        $this->log("bulk protect result");
        $this->log($status);

        $status['status'] = !empty($status['reason_status']) && 'ok' == $status['reason_status'];

        return $status;
    }

    private function formatToggleProtectionResponse($status, $newstatus, $domain)
    {
        $domain = $this->idn->decode($domain);

        if (true === $status['status']) {
            return array(
                'message' => sprintf($this->translator->_('The protection status of %s has been changed to <strong>%s</strong>'), $domain, $newstatus),
                'status' => 'success'
            );
        }

        $additionalInfo = (!empty($status['additional'])) ? '. ' . ((is_array($status['additional'])) ? implode(', ', $status['additional']) : $status['additional']) : '';;

        return $this->formatToggleProtectionErrorStatusResponse($domain, $newstatus, $status['rawresult'], $additionalInfo);
    }

    private function formatToggleProtectionErrorStatusResponse($domain, $newstatus, $hookError, $hookAdditionalInfo = null)
    {
        $message = null;

        switch ($hookError) {
            case SpamFilter_Hooks::SKIP_OWNER_VALIDATION_FAIL:
                $message = sprintf($this->translator->_("You're not allowed to operate on the domain '%s'"), htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'));
                break;

            case SpamFilter_Hooks::SKIP_PROCESS_OF_ADDON_DOMAINS_DISABLED:
                $message = $this->translator->_("Processing of addon- and parked domains is disabled in settings");
                break;

            case SpamFilter_Hooks::SKIP_OWNER_DOMAIN_NOT_FOUND:
                $message = $this->translator->_("Owner domain not given");
                break;

            case SpamFilter_Hooks::ALREADYEXISTS_NOT_OWNER:
                $reason = $this->translator->_(' you are not the owner of it.');
                break;

            case SpamFilter_Hooks::SKIP_EXTRA_ALIAS:
                $reason = $this->translator->_(' because subdomain, parked and addon domains will be treated as aliases.');
                break;

            case SpamFilter_Hooks::SKIP_ADDED_AS_ALIAS_NOT_DOMAIN:
                $reason = sprintf($this->translator->_(' because subdomain, parked and addon domains are treated as normal domains and "%s" is already added as an alias.'), $domain);
                break;

            case SpamFilter_Hooks::SKIP_ADDED_AS_DOMAIN_NOT_ALIAS:
                $reason = sprintf($this->translator->_(' because subdomain, parked and addon domains are treated as aliases and "%s" is already added as a normal domain.'), $domain);
                break;

            case SpamFilter_Hooks::SKIP_REMOTE:
                $reason = $this->translator->_(' because domain uses remote exchanger.');
                break;

            case SpamFilter_Hooks::SKIP_DATAINVALID:
                $reason = $this->translator->_(' because data is invalid.');
                break;

            case SpamFilter_Hooks::SKIP_UNKNOWN:
                $reason = $this->translator->_(' because unknown error occurred.');
                break;

            case SpamFilter_Hooks::SKIP_APIFAIL:
                $reason = $this->translator->_(' because API communication failed.');
                break;

            case SpamFilter_Hooks::SKIP_ALREADYEXISTS:
                $reason = $this->translator->_(' because domain already exists.');
                break;

            case SpamFilter_Hooks::SKIP_INVALID:
                $reason = $this->translator->_(' because domain is not valid.');
                break;

            case SpamFilter_Hooks::SKIP_NOROOT:
                $reason = $this->translator->_(' because root domain cannot be added.');
                break;

            case SpamFilter_Hooks::API_REQUEST_FAILED:
                $reason = $this->translator->_(' because API request has failed.');
                break;

            case SpamFilter_Hooks::DOMAIN_EXISTS:
                $reason = $this->translator->_(' because domain already exists.');
                break;

            case SpamFilter_Hooks::ALIAS_EXISTS:
                $reason = $this->translator->_(' because alias already exists.');
                break;

            case SpamFilter_Hooks::DOMAIN_LIMIT_REACHED:
                $reason = $this->translator->_(' because domain limit was reached.');
                break;

            case SpamFilter_Hooks::DOMAIN_HAS_FEATURE_DISABLED:
                $reason = $this->translator->_(' because this feature is disabled for this domain\'s package. To enable it, you need to update the feature list of the package assigned to this domain.');
                break;

            default:
                $reason = $hookAdditionalInfo ? '. ' . $hookAdditionalInfo : '';
                break;
        }

        if (! $message) {
            $message = sprintf($this->translator->_('The protection status of %s could not be changed to <strong>%s</strong>%s'), $domain, $newstatus, $reason);
        }

        return array(
            'message' => $message,
            'status' => 'error'
        );
    }

    /**
     * Check if alias exists for given domain
     *
     * @param string $alias
     * @param string $domain
     *
     * @return bool
     *
     * @throws Exception On api error
     */
    private function isAliasInSpampanel($alias, $domain)
    {
        if (! SpamFilter_Domain::exists($domain)) {
            return false;
        }

        $apiResponse = $this->api->domainalias()->list(array('domain' => $domain));

        if (! isset($apiResponse['reason'])) {
            return in_array($alias, $apiResponse);
        }

        throw new Exception("Could not determine if alias");
    }

    private function log($msg)
    {
        if (is_array($msg)) {
            $msg = var_export($msg, true);
        }

        $this->logger->info("Hook[ProtectionToggler] $msg");
    }
}
