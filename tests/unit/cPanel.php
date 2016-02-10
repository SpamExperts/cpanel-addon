<?php
/*
require_once ROOT . '/library/SpamFilter/PanelSupport/Cpanel.php';

class cPanel extends PHPUnit_Framework_TestCase
{
    protected $_isCPanelInstalled = false;

    final public function setUp()
    {
        if (!file_exists(SpamFilter_PanelSupport_Cpanel::PANEL_FILESYSTEM_LOCATION)) {
            mkdir(SpamFilter_PanelSupport_Cpanel::PANEL_FILESYSTEM_LOCATION, 0777, true);
        } else {
            $this->_isCPanelInstalled = true;
        }
    }

    final public function tearDown()
    {
        if (!$this->_isCPanelInstalled) {
            rmdir(SpamFilter_PanelSupport_Cpanel::PANEL_FILESYSTEM_LOCATION);
        }
    }
}

class LoggerMock
{
    public function __call($method, $args) {}
}

class SpamFilter_Configuration
{
    public function __get($field) {}
    public function __call($method, $args) {}
    public function getPassword() { return md5('hash'); }
}

class ConfigMock
{
    public $add_extra_alias = false;
    public $use_existing_mx = true;
    public $handle_only_localdomains = false;
    public $handle_extra_domains = true;
}

class WHMAPIMock
{
    public function getWhm()
    {
        return new WHMAPICallMock;
    }
}

class WHMAPICallMock
{
    public function api2_query()
    {
        return new WHMResponse1Mock;
    }

    public function makeQuery($method)
    {
        switch ($method) {
            case 'listmxs': return new WHMResponse2Mock;
            case 'listips': return new WHMResponse3Mock;
        }
        return new WHMResponse2Mock;
    }
}

class WHMResponse1Mock
{
    public function getResponse($type)
    {
        return array(
            'cpanelresult' => array(
                'data' => array(
                    array(
                        'detected' => 'auto',
                    )
                )
            )
        );
    }
}

class WHMResponse2Mock
{
    public function getResponse($type)
    {
        return array(
            'data' => array(
                'record' => '',
            )
        );
    }
}

class WHMResponse3Mock
{
    public function getResponse($type)
    {
        return array(
            'data' => array(
                'ip' => array(
                    array('ip' => '85.92.89.141',),
                    array('ip' => '85.92.89.142',),
                    array('ip' => '85.92.89.143',),
                    array('ip' => '85.92.89.144',),
                    array('ip' => '85.92.89.145',),
                    array('ip' => '85.92.89.146',),
                    array('ip' => '10.0.0.50',),
                    array('ip' => '81.19.187.243',),
                )
            )
        );
    }
}

class Zend_Registry
{
    static public function get($id)
    {
        switch ($id) {
            case 'logger': return new LoggerMock;
            case 'general_config': return new ConfigMock;
        }

        return $id;
    }

    static public function isRegistered($id) { return true; }
}

class Cpanel_PublicAPI
{
    static public function getInstance($whmconfig) { return new WHMAPIMock; }
}

class SpamFilter_Core
{
    static public function GetServerName()
    {
        return 'cpanel.spamexperts.com';
    }
}

class SpamFilter_Network_Utils
{
    static final public function getHostByNameL($hostname)
    {
        return (('titania.echointernet.net' == $hostname) ? '85.92.89.141' : $hostname);
    }
}
*/
