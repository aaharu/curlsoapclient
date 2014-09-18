<?php
/**
 * curlsoapclient - SoapClient with php-curl. -
 *
 * @author    aaharu
 * @copyright Copyright (c) 2014 aaharu
 * @license   MIT License
 */

namespace Aaharu\Soap;

class CurlSoapClient extends \SoapClient
{
    protected $curl = null;
    private $redirect_max = 0;

    public function __construct($wsdl, array $options)
    {
        parent::__construct($wsdl, $options);
    }

    public function __getCookies()
    {
        if (isset($this->_cookies)) {
            return $this->_cookies;
        }
        return null;
    }

    public function __setCookie($name, $value = null)
    {
        $this->_cookies = $name;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $this->curl = curl_init();
        $header = array('Connection: Keep-Alive');
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        if (isset($this->trace) && $this->trace) {
            curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
        }

        if (isset($this->compression)) {
            if ($this->compression & SOAP_COMPRESSION_ACCEPT) {
                curl_setopt($this->curl, CURLOPT_ENCODING, '');
            } elseif ($this->compression & SOAP_COMPRESSION_DEFLATE) {
                curl_setopt($this->curl, CURLOPT_ENCODING, 'deflate');
            } else {
                curl_setopt($this->curl, CURLOPT_ENCODING, 'gzip');
            }
        }
        if (isset($this->_user_agent) && is_string($this->_user_agent) && strlen($this->_user_agent) > 0) {
            curl_setopt($this->curl, CURLOPT_USERAGENT, $this->_user_agent);
        }
        if ($version === SOAP_1_2) {
            $header[] = "Content-Type: application/soap+xml; charset=utf-8; action=\"{$action}\"";
        } else {
            $header[] = 'Content-Type: text/xml; charset=utf-8';
            $header[] = "SOAPAction: \"{$action}\"";
        }

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request);

        $connection_timeout = 10; // default
        if (isset($this->_connection_timeout) && is_int($this->_connection_timeout)) {
            $connection_timeout = $this->_connection_timeout;
        }
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $connection_timeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30); // todo option

        $response = $this->_curlCall($location);
        if ($response === false) {
            throw new \SoapFault(
                'HTTP',
                'Error Fetching http, ' . curl_error($this->curl) . ' (' . curl_errno($this->curl) . ')'
            );
        }

        $response_header = substr($response, 0, curl_getinfo($this->curl, CURLINFO_HEADER_SIZE));
        $http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if (isset($this->trace) && $this->trace) {
            $this->__last_request_headers = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
            $this->__last_response_headers = $response_header;
        }

        if ($http_code >= 300 && $http_code < 400) {
            $tmp = stristr($response_header, 'Location:');
            $line_end = strpos($tmp, "\r");
            if ($line_end === false) {
                $line_end = strpos($tmp, "\n");
                if ($line_end === false) {
                    throw new \SoapFault('HTTP', 'Error Redirecting, No Location');
                }
            }
            // todo filter location
            $new_location = trim(substr($tmp, 9, $line_end));
            if (++$this->redirect_max > 5) { // todo option
                throw new \SoapFault('HTTP', 'Redirection limit reached, aborting');
            }
            $this->_curlCall($new_location, $header, $request);
        }

        // todo
        $is_xml = false;
        $content_type = curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
        if ($content_type !== null) {
            $separator_position = strpos($content_type, ';');
            if ($separator_position !== false) {
                $content_type = substr($content_type, 0, $separator_position);
            }
            if ($content_type === 'text/xml' || $content_type === 'application/soap+xml') {
                $is_xml = true;
            }
        }

        curl_close($this->curl);
        return $response;
    }

    private function _curlCall($location)
    {
        curl_setopt($this->curl, CURLOPT_URL, $location);

        // HTTP Authentication
        if (isset($this->_login) && is_string($this->_login)) {
            // TODO
        }

        if (isset($this->_cookies) && is_string($this->_cookies)) {
            curl_setopt($this->curl, CURLOPT_COOKIE, $this->_cookies);
        }

        return curl_exec($this->curl);
    }
}
