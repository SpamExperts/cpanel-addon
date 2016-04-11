<?php

namespace Helper;

use Codeception\Scenario;
use Symfony\Component\Yaml\Yaml;

class Acceptance extends \Codeception\Module
{
    private $cpanelApi;
    private $parameters;
    private $spampanelApi;

    public function checkAllBoxes($selector)
    {
        $webDriver = $this->getWebDriver();

        $elements = $webDriver->_findElements($selector);
        foreach ($elements as $element) {
            $this->getWebDriver()->checkOption($element);
        }
    }

    public function getElementsCount($selector)
    {
        return count($this->getWebDriver()->_findElements($selector));
    }

    public function clickOneOf(array $selectors)
    {
        foreach ($selectors as $selector) {
            if ($this->getElementsCount($selector)) {
                $this->getWebDriver()->click($selector);
                return;
            }
        }

        throw new \Exception("None of the following elements found: ".implode(', ', $selectors));
    }

    public function getElementsValues($selector)
    {
        $elements = $this->getWebDriver()->_findElements($selector);

        $values = [];

        foreach ($elements as $element) {
            $values[] = $element->getAttribute('value');
        }

        return $values;
    }

    public function seeInCurrentAbsoluteUrl($text)
    {
        $this->assertContains($text, $this->getWebDriver()->webDriver->getCurrentURL());
    }

    public function seeOneOf($texts)
    {
        $partialXpaths = array_map(function($text){
            return 'contains(text(), \''.addslashes($text).'\')';
        }, $texts);

        $selector = "//*[".implode(' or ', $partialXpaths)."]";
        codecept_debug($selector);
        $this->getWebDriver()->waitForElement($selector);
    }

    /**
     * @param Scenario $scenario
     */
    public function setWebDriverUrl($scenario)
    {
        $webDriver = $this->getWebDriver();

        $env = $scenario->current('env');
        $parameters = $this->getParsedParameters();

        if (isset($parameters['env'][$env]['url'])) {
            $webDriver->_setConfig([
                'url' => getenv($parameters['env'][$env]['url'])
            ]);
        }
        codecept_debug($webDriver->_getConfig()['url']);
    }

    public function setupCpanelApi(Scenario $scenario)
    {
        $parameters = $this->getParsedParameters();
        $env = $scenario->current('env');

        $url = getenv($parameters['env'][$env]['url']);
        $whmUsername = getenv($parameters['env'][$env]['username']);
        $whmAcessHash = getenv($parameters['env'][$env]['whm_access_hash']);

        $this->cpanelApi = new \CpanelApi($scenario, $url, $whmUsername, $whmAcessHash);
    }

    public function setupSpampanelApi(Scenario $scenario)
    {
        $this->spampanelApi = new \SpampanelApi($scenario);
    }

    public function assertDomainExistsInSpampanel($domain)
    {
        $domainExists = $this->makeSpampanelApiRequest()->domainExists($domain);
        $this->getAsserts()->assertTrue($domainExists);
    }

    public function assertDomainNotExistsInSpampanel($domain)
    {
        $domainExists = $this->makeSpampanelApiRequest()->domainExists($domain);
        $this->getAsserts()->assertFalse($domainExists);
    }
    
    public function assertIsAliasInSpampanel($alias, $domain)
    {
        $aliases = $this->makeSpampanelApiRequest()->getDomainAliases($domain);
        $this->getAsserts()->assertContains($alias, $aliases);
    }

    public function assertIsNotAliasInSpampanel($alias, $domain)
    {
        $aliases = $this->makeSpampanelApiRequest()->getDomainAliases($domain);
        $this->getAsserts()->assertNotContains($alias, $aliases);
    }

    /**
     * @return \CpanelApi
     */
    public function makeCpanelApiRequest()
    {
        return $this->cpanelApi;
    }

    /**
     * @return \SpampanelApi
     */
    public function makeSpampanelApiRequest()
    {
        return $this->spampanelApi;
    }

    /**
     * @return \Codeception\Module\WebDriver
     */
    private function getWebDriver()
    {
        return $this->getModule('WebDriver');
    }

    /**
     * @return \Codeception\Module\Asserts
     */
    private function getAsserts()
    {
        return $this->getModule('Asserts');
    }

    /**
     * @return array
     */
    private function getParsedParameters()
    {
        if (! $this->parameters) {
            $file = realpath(__DIR__ . '/../../acceptance.suite.yml');
            $this->parameters = Yaml::parse(file_get_contents($file));
        }

        return $this->parameters;
    }
}
