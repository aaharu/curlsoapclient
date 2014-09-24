curlsoapclient
==============

[![Build Status](https://travis-ci.org/aaharu/curlsoapclient.png?branch=master)](https://travis-ci.org/aaharu/curlsoapclient)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/?branch=master)

SoapClient with php-curl.

https://packagist.org/packages/aaharu/curlsoapclient


Documentation
--------------

### Aaharu\Soap\CurlSoapClient::CurlSoapClient

```php
public CurlSoapClient::CurlSoapClient ( mixed $wsdl [, array $options ] )
```

#### supported options

- soap_version
  - either `SOAP_1_1` or `SOAP_1_2`. If omitted, `SOAP_1_1` is used.
- compression
- trace
- exceptions
- connection_timeout
- user_agent
- keep_alive

#### additional options

- redirect_max
  - The maximum amount of HTTP redirections to follow. default is 5.
- curl_timeout
  - CURLOPT_TIMEOUT

#### unsupported options

- stream_context
  - not necessary with curl
- login
  - use `CurlSoapClient::___curlSetOpt` instead
- password
  - use `CurlSoapClient::___curlSetOpt` instead
- proxy_host
  - use `CurlSoapClient::___curlSetOpt` instead
- proxy_port
  - use `CurlSoapClient::___curlSetOpt` instead
- proxy_login
  - use `CurlSoapClient::___curlSetOpt` instead
- proxy_password
  - use `CurlSoapClient::___curlSetOpt` instead
- local_cert
  - use `CurlSoapClient::___curlSetOpt` instead
- passphrase
  - use `CurlSoapClient::___curlSetOpt` instead
- authentication
  - use `CurlSoapClient::___curlSetOpt` instead
- ssl_method
  - use `CurlSoapClient::___curlSetOpt` instead


Contributing
--------------

With [composer](https://getcomposer.org) installed, run the following from the root of the repository:

```sh
$ composer install
```

### Running the tests

```sh
$ vendor/bin/phpunit
```


License
--------------

Licensed under the MIT License.
