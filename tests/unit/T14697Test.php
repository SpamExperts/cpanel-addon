<?php

require_once __DIR__ . '/cPanel.php';

/* Commented until further clarification
class T14697Test extends cPanel
{
    const DUP_DOMAIN = 'dup.example.com';

    public function testMethodFiltersOutDuplicateDomains()
    {
        $whmApiResponse1Mock = $this->getMock('Cpanel_Query_Object', array('getResponse'));
        $whmApiResponse1Mock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue(array(
                'acct' => array('acc1.com', 'acc2.org'),
            )));

        $cpanelApiMock = $this->getMock('Cpanel_PublicAPI', array('whm_api', 'getWhm'));
        $cpanelApiMock->expects($this->once())
            ->method('whm_api')
            ->will($this->returnValue($whmApiResponse1Mock));

        $whmApiResponse2Mock = $this->getMock('Cpanel_Query_Object', array('getResponse'));
        $whmApiResponse2Mock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue(array(
                'cpanelresult' => array(
                    'data' => array(
                        array(
                            'domain' => self::DUP_DOMAIN,
                            'rootdomain' => 'somedomain.com',
                        ),
                    ),
                ),
            )));

        $whmApiMock = $this->getMock('Cpanel_Query_Interface', array('api2_query'));
        $whmApiMock->expects($this->any())
            ->method('api2_query')
            ->will($this->returnValue($whmApiResponse2Mock));

        $cpanelApiMock->expects($this->any())
            ->method('getWhm')
            ->will($this->returnValue($whmApiMock));

        $model = new SpamFilter_PanelSupport_Cpanel(array(
            'cpanel_api_instance' => $cpanelApiMock,
        ));
        $domains = $model->getCollectionDomains(false);

        $matchesFound = 0;
        foreach ($domains as $entry) {
            if ($entry['name'] == self::DUP_DOMAIN) {
                $matchesFound++;
            }
        }

        $this->assertEquals(1, $matchesFound);
    }

    public function testMultidimArrayUnique()
    {
        $cases = array(
            array(
                'array' => array(
                    array(
                        'name' => 'one',
                        'stuff' => 'st',
                    ),
                    array(
                        'name' => 'one',
                        'stuff' => 'uff',
                    ),
                ),
                'sorted' => array(
                    array(
                        'name' => 'one',
                        'stuff' => 'st',
                    ),
                ),
            ),
            array(
                'array' => array(
                    array(
                        'name' => 'two',
                        'stuff' => 'st',
                    ),
                    array(
                        'name' => 'three',
                        'stuff' => 'uff',
                    ),
                ),
                'sorted' => array(
                    array(
                        'name' => 'two',
                        'stuff' => 'st',
                    ),
                    array(
                        'name' => 'three',
                        'stuff' => 'uff',
                    ),
                ),
            ),
            array(
                'array' => array(),
                'sorted' => array(),
            ),
        );

        foreach ($cases as $case) {
            $this->assertEquals($case['sorted'],
                SpamFilter_PanelSupport_Cpanel::multidimArrayUnique($case['array'], 'name'));
        }
    }
}
*/

/*
class SpamFilter_Panel_Cache
{
    static public function get() { return false; }
    static public function set() { return false; }
}

class SpamFilter_Panel_Account
{
    private $_acc;
    public function __construct($acc) { $this->_acc = $acc; }
    public function getUser() { return $this->_acc; }
    public function getDomain() { return $this->_acc; }
}*/
