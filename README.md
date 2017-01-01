curlsoapclient
==============

[![Build Status](https://travis-ci.org/aaharu/curlsoapclient.png?branch=master)](https://travis-ci.org/aaharu/curlsoapclient)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/?branch=master)

A SoapClient wrapper that uses ext-curl.

https://packagist.org/packages/aaharu/curlsoapclient


Documentation
--------------

### Aaharu\Soap\CurlSoapClient::CurlSoapClient

This class is extended [SoapClient](http://php.net/manual/class.soapclient.php).

```php
public CurlSoapClient::CurlSoapClient ( mixed $wsdl [, array $options ] )
```

#### supported options

- soap\_version
  - either `SOAP_1_1` or `SOAP_1_2`. If omitted, `SOAP_1_1` is used.
- compression
- trace
- exceptions
- connection\_timeout
- user\_agent
- keep\_alive
  - `true` as a default
- login
- password
- proxy\_host
- proxy\_port
- proxy\_login
- proxy\_password
- authentication
- ssl\_method

#### additional options

- redirect\_max
  - The maximum amount of HTTP redirections to follow. default is 5.
  - `5` as a default
- curl\_timeout
  - CURLOPT\_TIMEOUT
  - `30` as a default

#### unsupported options

- stream\_context
  - not necessary with curl
- local\_cert
  - use `CurlSoapClient::___curlSetOpt` instead
- passphrase
  - use `CurlSoapClient::___curlSetOpt` instead

#### examples

wsdl mode.

```php
use Aaharu\Soap\CurlSoapClient;

try {
    $client = new CurlSoapClient('http://webservices.amazon.com/AWSECommerceService/2013-08-01/AWSECommerceService.wsdl', array('trace' => true));
    $client->ItemLookup();
} catch (\SoapFault $fault) {
}

echo $client->__getLastRequestHeaders() . $client->__getLastRequest();
```

```
POST /onca/soap?Service=AWSECommerceService HTTP/1.1
Host: webservices.amazon.com
Accept: */*
Connection: Keep-Alive
Content-Type: text/xml; charset=utf-8
SOAPAction: "http://soap.amazon.com/ItemLookup"
Content-Length: 259

<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://webservices.amazon.com/AWSECommerceService/2013-08-01"><SOAP-ENV:Body><ns1:ItemLookup/></SOAP-ENV:Body></SOAP-ENV:Envelope>
```

non-wsdl mode.

```php
use Aaharu\Soap\CurlSoapClient;

$client = new CurlSoapClient(null, array(
    'location' => 'http://example.com/test/location',
    'uri' => 'http://example.com/test/uri',
    'redirect_max' => 1,
    'exceptions' => false,
));
$client->doSomething();
```


Contributing
--------------

With [composer](https://getcomposer.org) installed, run the following from the root of the repository:

```sh
$ composer install
```

### Running the tests

```sh
$ composer test
```


License
--------------

Licensed under the MIT License.
