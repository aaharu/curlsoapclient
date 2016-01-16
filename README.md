curlsoapclient
==============

[![Build Status](https://travis-ci.org/aaharu/curlsoapclient.png?branch=master)](https://travis-ci.org/aaharu/curlsoapclient)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/aaharu/curlsoapclient/?branch=master)

SoapClient with ext-curl.

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
  - `true` as a default
- login
- password
- proxy_host
- proxy_port
- proxy_login
- proxy_password
- authentication

#### additional options

- redirect_max
  - The maximum amount of HTTP redirections to follow. default is 5.
  - `5` as a default
- curl_timeout
  - CURLOPT_TIMEOUT
  - `30` as a default

#### unsupported options

- stream_context
  - not necessary with curl
- local_cert
  - use `CurlSoapClient::___curlSetOpt` instead
- passphrase
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
$ composer test
```


License
--------------

Licensed under the MIT License.
