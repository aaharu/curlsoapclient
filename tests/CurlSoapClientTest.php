<?php
namespace Aaharu\Soap\CurlSoapClient\Tests;

use Aaharu\Soap\CurlSoapClient;

/**
 * @coversDefaultClass \Aaharu\Soap\CurlSoapClient
 */
class CurlSoapClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function soap1_1()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php',
            'uri' => 'http://test-uri/',
            'compression' => SOAP_COMPRESSION_ACCEPT,
            'connection_timeout' => 1
        ));

        $response = $obj->test(123);
        $this->assertEquals(123, $response);
    }

    /**
     * @test
     */
    public function soap1_2()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?redirect=2',
            'uri' => 'http://test-uri/',
            'user_agent' => 'curlsoapclient',
            'soap_version' => SOAP_1_2,
            'trace' => true
        ));

        $response = $obj->__soapCall('test', array(123));
        $this->assertEquals(123, $response);

        $last_request_headers = $obj->__getLastRequestHeaders();
        $this->assertTrue(stripos($last_request_headers, 'User-Agent: curlsoapclient') !== false);
    }
}
