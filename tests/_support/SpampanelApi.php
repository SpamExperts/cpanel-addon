<?php

use Codeception\Scenario;

class SpampanelApi
{
    /**
     * @var Scenario
     */
    private $scenario;

    public function __construct(Scenario $scenario)
    {
        $this->scenario = $scenario;
    }

    public function domainExists($domain)
    {
        $this->comment("Checking if $domain exists in spampanel api");
        $response = $this->requestApiUrl('domain/exists', ['domain' => $domain]);
        $this->checkResponseStatus($response);
        $data = json_decode($response['output'], true);

        return (boolean) $data['present'];
    }

    public function getDomainRoutes($domain)
    {
        $this->comment("Getting $domain routes");
        $response = $this->requestApiUrl('domain/getroute/format/json', ['domain' => $domain]);
        $this->checkResponseStatus($response);
        $response = json_decode($response['output'], true);

        if (! empty($response['messages']['error'])) {
            return [];
        }

        return $response['result'];
    }

    public function getDomainRoutesNames($domain)
    {
        $routes = $this->getDomainRoutes($domain);

        return array_map(function ($route) {
            list($route, $port) = explode('::', $route);
            return $route;
        }, $routes);
    }

    public function getDomainAliases($domain)
    {
        $this->comment("Getting $domain aliases");
        $response = $this->requestApiUrl('domainalias/list/format/json', ['domain' => $domain]);
        $this->checkResponseStatus($response);
        $response = json_decode($response['output'], true);

        if (! empty($response['messages']['error'])) {
            return [];
        }

        return $response['result'];
    }

    public function addDomainAlias($alias, $domain)
    {
        $this->comment("Adding alias $alias to domain $domain");
        $response = $this->requestApiUrl('domainalias/add/format/json', ['domain' => $domain, 'alias' => $alias]);
        $this->checkResponseStatus($response);
        $response = json_decode($response['output'], true);

        if (! empty($response['messages']['error'])) {
            throw new RuntimeException("Api error: ".var_export($response, true));
        }
    }

    public function requestApiUrl($url, array $params = array())
    {
        $url = \PsfConfig::getApiUrl().'/api/'.$url;

        foreach ($params as $name => $value) {
            if (is_array($value)) {
                $value = array_map(function($val){return '"'.$val.'"';}, $value);
                $value = '['.implode(',', $value).']';
            }
            $url .= '/'.$name.'/'.rawurlencode($value);
        }

        $response = $this->requestUrl($url, \PsfConfig::getApiUsername(), \PsfConfig::getApiPassword());

        codecept_debug("Making api request: ".$url);

        return $response;
    }

    public function requestUrl($url, $username = null, $password = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($username && $password) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        }

        $output =
            curl_exec($ch);

        $response = [
            'output' => $output,
            'info' => curl_getinfo($ch)
        ];

        curl_close($ch);

        return $response;
    }

    private function checkResponseStatus($response)
    {
        if (200 != $response['info']['http_code']) {
            throw new RuntimeException("Invalid api status code ".$response['info']['http_code']);
        }
    }

    private function comment($string)
    {
        $this->scenario->comment($string);
    }
}
