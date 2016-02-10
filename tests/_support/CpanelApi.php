<?php

use Codeception\Scenario;

class CpanelApi
{
    /**
     * @var Scenario
     */
    private $scenario;
    private $baseUrl;
    private $whmUsername;
    private $whmAccessHash;

    public function __construct(Scenario $scenario, $baseUrl, $whmUsername, $whmAccessHash)
    {
        $this->scenario = $scenario;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->whmUsername = $whmUsername;
        $this->whmAccessHash = $whmAccessHash;
    }

    public function addAccount(array $params)
    {
        $this->comment("I create account");
        $this->request('createacct', $params);
    }

    public function deleteAccount($username)
    {
        $this->comment("I delete account $username");
        $this->request('removeacct', ['username' => $username]);
    }

    private function comment($message)
    {
        $this->scenario->comment($message);
    }

    private function request($method, array $params = array(), $requestType = 'GET')
    {
        $params = array_merge([
            'api.version' => 1,
        ], $params);

        $query = $this->baseUrl."/json-api/$method?".http_build_query($params);
        $headers = [];
        $headers[] = "Authorization: WHM $this->whmUsername:" . preg_replace("'(\r|\n)'", "", $this->whmAccessHash);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $query);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 400);

        codecept_debug($query);
        $result = curl_exec($curl);

        if ($result == false) {
            throw new RuntimeException("curl_exec threw error \"" . curl_error($curl) . "\" for $query");
        }

        curl_close($curl);
        codecept_debug(substr($result, 0, 200).'...');
//        codecept_debug($result);

        return $result;
    }
}