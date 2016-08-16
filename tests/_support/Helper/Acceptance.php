<?php

namespace Helper;

use Codeception\Scenario;
use Symfony\Component\Yaml\Yaml;

class Acceptance extends \Codeception\Module
{
    private $cpanelApi;
    private $parameters;
    private $spampanelApi;

    /**
     * Function used to check all the boxes from a page
     * @param $selector - array of checkbox locators
     */
    public function checkAllBoxes($selector)
    {
        $webDriver = $this->getWebDriver();

        $elements = $webDriver->_findElements($selector);
        foreach ($elements as $element) {
            $this->getWebDriver()->checkOption($element);
        }
    }

    /**
     * Function used to count number of elements from a page
     * @param $selector - selector of the element
     * @return int - count of elements
     */
    public function getElementsCount($selector)
    {
        return count($this->getWebDriver()->_findElements($selector));
    }

    /**
     * Function used to click one of the elements from the array given as parameter
     * @param array $selectors - array of selectors
     * @throws \Exception
     */
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

        $parameters = $this->getParsedParameters();

        if (isset($parameters['env']['url'])) {
            $webDriver->_setConfig([
                'url' => getenv($parameters['env']['url'])
            ]);
        }
        codecept_debug($webDriver->_getConfig()['url']);
    }

    public function setupCpanelApi(Scenario $scenario)
    {
        $parameters = $this->getParsedParameters();

        $url = getenv($parameters['env']['url']);
        $whmUsername = getenv($parameters['env']['username']);
        $whmAcessHash = getenv($parameters['env']['whm_access_hash']);

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
     * Function used to make a cPanel API request
     */
    public function makeCpanelApiRequest()
    {
        return $this->cpanelApi;
    }

    /**
     * Function used to make a spampanel API request
     */
    public function makeSpampanelApiRequest()
    {
        return $this->spampanelApi;
    }

    /**
     * Function used to obtain the Web Driver
     */
    private function getWebDriver()
    {
        return $this->getModule('WebDriver');
    }

    /**
     * Function used to obtain the asserts
     */
    private function getAsserts()
    {
        return $this->getModule('Asserts');
    }

    /**
     * Function used to parse YML file
     * @return array - array with parameters of the yml file
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
