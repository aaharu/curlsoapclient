<?php
namespace Aaharu\Soap\CurlSoapClient\Tests;

use Aaharu\Soap\CurlSoapClient;

/**
 * @coversDefaultClass \Aaharu\Soap\CurlSoapClient
 */
class CurlSoapClientTest extends \PHPUnit_Framework_TestCase
{
    protected $obj = null;

    protected function setUp()
    {
        $this->obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php',
            'uri' => "http://test-uri/",
            'compression' => SOAP_COMPRESSION_ACCEPT,
            'trace' => true
        ));
    }

    protected function tearDown()
    {
        unset($this->obj);
    }

    /**
     * @test
     */
    public function soapCall()
    {
        $response = $this->obj->__soapCall('test', array(123));
        $this->assertEquals(123, $response);
    }

}
