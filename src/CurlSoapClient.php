<?php
/**
 * curlsoapclient - SoapClient with php-curl. -
 *
 * @author    aaharu
 * @copyright Copyright (c) 2014 aaharu
 * @license   MIT License
 */

namespace Aaharu\Soap;

/**
 * @see https://github.com/php/php-src/tree/master/ext/soap
 */
class CurlSoapClient extends \SoapClient
{
    protected $curl = null; ///< cURL handle
    protected $redirect_max; ///< max redirect counts
    protected $curl_timeout; ///< cURL request time-out seconds
    private $redirect_count = 0;
    private $curlopts = array();

    public function __construct($wsdl, array $options)
    {
        parent::__construct($wsdl, $options);
        $this->redirect_max = 5;
        if (isset($options['redirect_max'])) {
            $this->redirect_max = (int) $options['redirect_max'];
        }
        $this->curl_timeout = 30;
        if (isset($options['curl_timeout'])) {
            $this->curl_timeout = (int) $options['curl_timeout'];
        }
        $this->curl = curl_init();
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
        $this->curlopts[$option] = $value;
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

    /**
     * Execute SOAP requests.
     *
     * @param[in] string $request SOAP request
     * @param[in] string $location SOAP address
     * @param[in] string $action SOAP action
     * @param[in] int $version SOAP version
     * @param[in] int $one_way
     * @return mixed (string) SOAP response / (object) SoapFault object
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
        if (isset($this->_user_agent) && is_string($this->_user_agent) && strlen($this->_user_agent) > 0) {
            curl_setopt($this->curl, CURLOPT_USERAGENT, $this->_user_agent);
        }

        try {
            $response = $this->___curlCall($location);
        } catch (\SoapFault $fault) {
            if (isset($this->_exceptions) && empty($this->_exceptions)) {
                // if exceptions option is false, retrun \SoapFault object
                return $fault;
            }
            throw $fault;
        }

        if ($one_way) {
            return '';
        }

        return $response;
    }

    private function ___configHeader($action, $version)
    {
        $header = array();
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

    private function ___configCompression()
    {
        if (isset($this->compression)) {
            if ($this->compression & SOAP_COMPRESSION_ACCEPT) {
                curl_setopt($this->curl, CURLOPT_ENCODING, '');
            } elseif ($this->compression & SOAP_COMPRESSION_DEFLATE) {
                curl_setopt($this->curl, CURLOPT_ENCODING, 'deflate');
            } else {
                curl_setopt($this->curl, CURLOPT_ENCODING, 'gzip');
            }
        }
    }

    private function ___configTimeout()
    {
        $connection_timeout = 10; // default
        if (isset($this->_connection_timeout) && is_int($this->_connection_timeout)) {
            $connection_timeout = $this->_connection_timeout;
        }
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $connection_timeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->curl_timeout);
    }

    /**
     * Request cURL.
     *
     * @param[in] string $location SOAP address
     * @throw \SoapFault
     */
    private function ___curlCall($location)
    {
        curl_setopt($this->curl, CURLOPT_URL, $location);

        if (isset($this->_cookies) && is_string($this->_cookies)) {
            curl_setopt($this->curl, CURLOPT_COOKIE, $this->_cookies);
        }

        $response = curl_exec($this->curl);
        if ($response === false) {
            throw new \SoapFault(
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
                throw new \SoapFault('HTTP', 'Error Redirecting, No Location');
            }
            $new_location = trim(substr($tmp, 9, $line_end - 9));
            $url = parse_url($new_location);
            if ($url === false ||
                empty($url['scheme']) ||
                preg_match('/^https?$/i', $url['scheme']) !== 1) {
                throw new \SoapFault('HTTP', 'Error Redirecting, Invalid Location');
            }
            if (++$this->redirect_count > $this->redirect_max) {
                throw new \SoapFault('HTTP', 'Redirection limit reached, aborting');
            }
            return $this->___curlCall($new_location);
        }

        if ($http_code >= 400) {
            $is_error = false;
            $response_length = strlen($response_body);
            if ($response_length === 0) {
                $is_error = true;
            } elseif ($response_length > 0) {
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
                if (!$is_xml) {
                    $str = ltrim($response_body);
                    if (strncmp($str, '<?xml', 5)) {
                        $is_error = true;
                    }
                }
            }

            if ($is_error) {
                $string_http_code = (string) $http_code;
                $code_position = strpos($response_header, $string_http_code);
                $tmp = substr($response_header, $code_position + strlen($string_http_code));
                $http_message = trim(strstr($tmp, "\n", true));
                throw new \SoapFault('HTTP', $http_message);
            }
        }

        return $response_body;
    }
}
