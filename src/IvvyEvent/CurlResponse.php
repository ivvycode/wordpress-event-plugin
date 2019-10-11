<?php

namespace IvvyEvent;

/**
 * iVvy Events
 *
 * @category   Event
 * @package    iVvy WordPress Project
 * @subpackage Event Registration
 * @copyright  Copyright (c) iVvy Pty Ltd
 * @version    $Id$
 */

/**
 * Curl response class
 *
 * @category   Event
 * @package    iVvy WordPress Project
 * @subpackage Event Registration
 * @copyright  Copyright (c) iVvy Pty Ltd
 */
class CurlResponse
{
    /**
     * Constant for redirect response http status code
     */
    const REDIRECT_STATUS_CODE = 302;

    /**
     * The response body
     *
     * @var string
     */
    protected $_body;

    /**
     * The response headers
     *
     * @var array
     */
    protected $_headers;

    /**
     * The raw response headers
     *
     * @var array
     */
    protected $_rawHeaders;

    /**
     * The response http status code
     *
     * @var int
     */
    protected $_status;

    /**
     * Constructor.
     *
     * @param resource $curlObject
     * @param resource $curlResponse
     */
    public function __construct($curlObject, $curlResponse)
    {
        $this->parseResponse($curlObject, $curlResponse);
        $this->_status = curl_getinfo($curlObject, CURLINFO_HTTP_CODE);
    }

    /**
     * Parses the response and returns header and body from response
     *
     * @param resource $curl
     * @param mixed $response
     * @return array
     */
    public function parseResponse($curl, $response)
    {
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header_string = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $header_rows = array_filter(explode(PHP_EOL, $header_string), function($string) { return trim($string); });
        $i = 0;
        $j = 0;
        $headers = array();
        foreach ((array) $header_rows as $hr) {
            $colonpos = strpos($hr, ':');
            $key = $colonpos !== false ? substr($hr, 0, $colonpos) : (int) $i++;
            $headers[$key] = $colonpos !== false ? trim(substr($hr, $colonpos + 1)) : $hr;
        }
        $this->_rawHeaders = $headers;

        foreach ((array) $headers as $key => $val) {
            $vals = explode(';', $val);
            if (count($vals) >= 2) {
                unset($headers[$key]);
                foreach ($vals as $vv) {
                    $equalpos = strpos($vv, '=');
                    $vkey = $equalpos !== false ? trim(substr($vv, 0, $equalpos)) : (int) $j++;
                    $headers[$key][$vkey] = $equalpos !== false ? trim(substr($vv, $equalpos + 1)) : $vv;
                }
            }
        }

        $this->_headers = $headers;
        $this->_body = $body;
    }

    /**
     * Returns response redirect location
     *
     * @return string
     */
    public function getRedirectLocation()
    {
        return $this->_headers['Location'];
    }

    /**
     * Whether the response is json response
     *
     * @return boolean
     */
    public function isRedirectResponse()
    {
        return $this->getHttpStatus() == self::REDIRECT_STATUS_CODE;
    }

    /**
     * Whether the response is json response
     *
     * @return boolean
     */
    public function isJsonResponse()
    {
        if (!$this->_headers) {
            return false;
        }

        if ($this->_headers['Content-Type'] == 'application/json') {
            return true;
        }

        return false;
    }

    /**
     * Whether response is html response
     *
     * @return boolean
     */
    public function isHtmlResponse()
    {
        if (!$this->_headers) {
            return false;
        }

        if ($this->_headers['Content-Type'] == 'text/html') {
            return true;
        }

        return false;
    }

    /**
     * Whether response is html response
     *
     * @return string
     */
    public function getCookieString()
    {
        return isset($this->_rawHeaders['Set-Cookie']) ? $this->_rawHeaders['Set-Cookie'] : null;
    }

    /**
     * Returns response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * Returns Http status
     *
     * @return int
     */
    public function getHttpStatus()
    {
        return $this->_status;
    }
}

