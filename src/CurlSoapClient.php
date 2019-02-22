<?php
/**
 * curlsoapclient - SoapClient with ext-curl. -
 *
 * @author    aaharu
 * @copyright Copyright (c) 2014 aaharu
 * @license   MIT License
 */

namespace Aaharu\Soap;

use SoapClient;
use SoapFault;

/**
 * @see https://github.com/php/php-src/tree/master/ext/soap
 */
class CurlSoapClient extends SoapClient
{
    protected $curl = null; ///< cURL handle
    protected $redirect_max; ///< max redirect counts
    protected $curl_timeout; ///< cURL request time-out seconds
    protected $proxy_type = CURLPROXY_HTTP; ///< proxy_type such as http, socks4, socks5
    private $redirect_count = 0;

    public function __construct($wsdl, array $options)
    {
        parent::__construct($wsdl, $options);
        $this->redirect_max = 5;
        if (isset($options['redirect_max'])) {
            $this->redirect_max = (int)$options['redirect_max'];
        }
        $this->curl_timeout = 30;
        if (isset($options['curl_timeout'])) {
            $this->curl_timeout = (int)$options['curl_timeout'];
        }
        if (isset($options['proxy_type']) &&
            in_array($options['proxy_type'], ['http', 'socks4', 'socks5'], true)) {
            $this->proxy_type = $options['proxy_type'];
        }
        $this->curl = curl_init();
        $this->_cookies = [];
    }

    public function __destruct()
    {
        if (isset($this->curl)) {
            curl_close($this->curl);
        }
    }

    public function ___curlSetOpt($option, $value)
    {
        curl_setopt($this->curl, $option, $value);
    }

    public function __getCookies()
    {
        return $this->_cookies;
    }

    public function __setCookie($name, $value = null)
    {
        if (!isset($value)) {
            unset($this->_cookies[$name]);
            return;
        }
        $this->_cookies[$name] = (array)$value;
    }

    /**
     * Execute SOAP requests.
     *
     * @param string $request SOAP request
     * @param string $location SOAP address
     * @param string $action SOAP action
     * @param int $version SOAP version
     * @param int $one_way
     * @throws \Exception
     * @throws \SoapFault
     * @return string|object (string) SOAP response / (object) SoapFault object
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request);
        if (isset($this->trace) && $this->trace) {
            curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
        }

        $this->___configHeader($action, $version);
        $this->___configCompression();
        $this->___configTimeout();
        if (!$this->___isEmptyExtProperty('_user_agent')) {
            curl_setopt($this->curl, CURLOPT_USERAGENT, $this->_user_agent);
        }
        $this->___configHttpAuthentication();
        $this->___configProxy();
        if (!$this->___isEmptyExtProperty('_ssl_method')) {
            switch ($this->_ssl_method) {
                case SOAP_SSL_METHOD_SSLv2:
                    curl_setopt($this->curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_SSLv2);
                    break;
                case SOAP_SSL_METHOD_SSLv3:
                    curl_setopt($this->curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_SSLv3);
                    break;
                default:
                    curl_setopt($this->curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
                    break;
            }
        }

        try {
            $response = $this->___curlCall($location);
        } catch (SoapFault $fault) {
            if (isset($this->_exceptions) && empty($this->_exceptions)) {
                // if exceptions option is false, return SoapFault object
                return $fault;
            }
            throw $fault;
        }

        if ($one_way) {
            return '';
        }

        return $response;
    }

    /**
     * set CURLOPT_HTTPHEADER.
     *
     * @param string $action SOAP action
     * @param int $version SOAP version
     */
    private function ___configHeader($action, $version)
    {
        $header = [];
        if (isset($this->_keep_alive) && empty($this->_keep_alive)) {
            $header[] = 'Connection: close';
        } else {
            $header[] = 'Connection: Keep-Alive';
        }
        if ($version === SOAP_1_2) {
            $header[] = "Content-Type: application/soap+xml; charset=utf-8; action=\"{$action}\"";
        } else {
            $header[] = 'Content-Type: text/xml; charset=utf-8';
            $header[] = "SOAPAction: \"{$action}\"";
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
    }

    /**
     * set CURLOPT_ENCODING.
     */
    private function ___configCompression()
    {
        if (!isset($this->compression)) {
            return;
        }
        if ($this->compression & SOAP_COMPRESSION_ACCEPT) {
            curl_setopt($this->curl, CURLOPT_ENCODING, '');
        } elseif ($this->compression & SOAP_COMPRESSION_DEFLATE) {
            curl_setopt($this->curl, CURLOPT_ENCODING, 'deflate');
        } else {
            curl_setopt($this->curl, CURLOPT_ENCODING, 'gzip');
        }
    }

    /**
     * set CURLOPT_CONNECTTIMEOUT and CURLOPT_TIMEOUT.
     */
    private function ___configTimeout()
    {
        $connection_timeout = 10; // default
        if (!$this->___isEmptyExtProperty('_connection_timeout')) {
            $connection_timeout = $this->_connection_timeout;
        }
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $connection_timeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->curl_timeout);
    }

    /**
     * set CURLOPT_USERPWD and CURLOPT_HTTPAUTH.
     */
    private function ___configHttpAuthentication()
    {
        if ($this->___isEmptyExtProperty('_login') || $this->___isEmptyExtProperty('_password')) {
            return;
        }
        curl_setopt($this->curl, CURLOPT_USERPWD, $this->_login . ':' . $this->_password);
        if (property_exists($this, '_digest')) {
            curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANYSAFE);
        } else {
            curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        }
    }

    /**
     * set proxy options.
     */
    private function ___configProxy()
    {
        if ($this->___isEmptyExtProperty('_proxy_host')) {
            return;
        }
        curl_setopt($this->curl, CURLOPT_PROXY, $this->_proxy_host);
        if (!$this->___isEmptyExtProperty('_proxy_port')) {
            curl_setopt($this->curl, CURLOPT_PROXYPORT, $this->_proxy_port);
        }
        if (!$this->___isEmptyExtProperty('_proxy_login') && !$this->___isEmptyExtProperty('_proxy_password')) {
            curl_setopt($this->curl, CURLOPT_PROXYUSERPWD, $this->_proxy_login . ':' . $this->_proxy_password);
        }
        if (property_exists($this, '_digest')) {
            curl_setopt($this->curl, CURLOPT_PROXYAUTH, CURLAUTH_ANYSAFE);
        } else {
            curl_setopt($this->curl, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
        }
        if ($this->proxy_type === 'socks5') {
            curl_setopt($this->curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        } elseif ($this->proxy_type === 'socks4') {
            curl_setopt($this->curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
        } elseif ($this->proxy_type === 'http') {
            // @see http://stackoverflow.com/questions/12288956/what-is-the-curl-option-curlopt-httpproxytunnel-means
            curl_setopt($this->curl, CURLOPT_HTTPPROXYTUNNEL, true);
        }
    }

    /**
     * Request cURL.
     *
     * @param string $location SOAP address
     * @param string $location
     * @throws \SoapFault
     * @return mixed response body
     */
    private function ___curlCall($location)
    {
        curl_setopt($this->curl, CURLOPT_URL, $location);

        if (!empty($this->_cookies)) {
            $cookies = [];
            foreach ($this->_cookies as $cookie_name => $cookie_value) {
                $cookies[] = $cookie_name . '=' . $cookie_value[0];
            }
            curl_setopt($this->curl, CURLOPT_COOKIE, implode('; ', $cookies));
        }

        $response = curl_exec($this->curl);
        if ($response === false) {
            throw new SoapFault(
                'HTTP',
                'Error Fetching http, ' . curl_error($this->curl) . ' (' . curl_errno($this->curl) . ')'
            );
        }

        $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $response_header = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);
        $http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if (isset($this->trace) && $this->trace) {
            $this->__last_request_headers = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
            $this->__last_response_headers = $response_header;
        }

        if ($http_code >= 300 && $http_code < 400) {
            $tmp = stristr($response_header, 'Location:');
            $line_end = strpos($tmp, "\n"); // "\r" will be trimmed
            if ($line_end === false) {
                throw new SoapFault('HTTP', 'Error Redirecting, No Location');
            }
            $new_location = trim(substr($tmp, 9, $line_end - 9));
            $url = parse_url($new_location);
            if ($url === false ||
                empty($url['scheme']) ||
                preg_match('/^https?$/i', $url['scheme']) !== 1
            ) {
                throw new SoapFault('HTTP', 'Error Redirecting, Invalid Location');
            }
            if (++$this->redirect_count > $this->redirect_max) {
                throw new SoapFault('HTTP', 'Redirection limit reached, aborting');
            }
            return $this->___curlCall($new_location);
        }

        if ($http_code >= 400 && $this->___isErrorResponse($response_body)) {
            $string_http_code = (string)$http_code;
            $code_position = strpos($response_header, $string_http_code);
            $tmp = substr($response_header, $code_position + strlen($string_http_code));
            $http_message = trim(strstr($tmp, "\n", true));
            throw new SoapFault('HTTP', $http_message);
        }

        return $response_body;
    }

    /**
     * check body is XML or not
     *
     * @param string $response_body server response body
     * @return boolean
     */
    private function ___isErrorResponse($response_body)
    {
        $response_length = strlen($response_body);
        if ($response_length === 0) {
            return true;
        }
        $content_type = curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
        if (!empty($content_type)) {
            $tmp = explode(';', $content_type, 2);
            $content_type = $tmp[0];
        }
        if ($content_type === 'text/xml' || $content_type === 'application/soap+xml') {
            return false;
        }
        $str = ltrim($response_body);
        if (strncmp($str, '<?xml', 5)) {
            return true;
        }
        return false;
    }


    /**
     * SoapClient property util
     *
     * @param string $property property name
     * @return boolean
     */
    private function ___isEmptyExtProperty($property)
    {
        if (!isset($this->{$property})) {
            return true;
        }
        if (is_string($this->{$property})) {
            return strlen($this->{$property}) === 0;
        }

        return false;
    }
}
