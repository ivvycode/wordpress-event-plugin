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
 * This is general class for handling curl request between iVvy event and WordPress
 *
 * @category   Event
 * @package    iVvy WordPress Project
 * @subpackage Event Registration
 * @copyright  Copyright (c) iVvy Pty Ltd
 */
class CurlClient
{
    /**
     * General curl related properties
     */
    protected $_useragent = 'Wordpress_Plugin';
    protected $_url = "";
    protected $_followlocation = "";
    protected $_timeout = 30;
    protected $_maxRedirects = 4;
    protected $_cookieFileLocation = null;
    protected $_post = array();
    protected $_postFields = array();
    protected $_files = array();
    protected $_referer = '';
    protected $_includeHeader = true;
    protected $_noBody = false;
    protected $_binaryTransfer = false;
    protected $_binary = false;
    protected $_cookie = null;
    protected $_headers = array();
    protected $_tmpDir = '/tmp/';
    protected $_cookieFileName = 'cookie.txt';
    protected $_useCookieFile = true;

    /**
     * Authentication if needed
     *
     * @var array
     */
    public $authentication = 0;
    public $auth_name = '';
    public $auth_pass = '';

    /**
     * The cookie name to store the ivvy cookie details in
     *
     * @var string
     */
    public $cUrlCookieName = 'ivSID';

    /**
     * Constructor.
     *
     * @param string $url
     * @param array $options
     * @throws Exception
     */
    public function __construct($url, $options = array())
    {
        $options += array(
            'followlocation' => false,
            'timeOut' => 30,
            'maxRedirecs' => 4,
            'binaryTransfer' => false,
            'includeHeader' => true,
            'noBody' => false,
            'cookie' => null,
            'referer' => $_SERVER['PHP_SELF'],
            'useragent' => $_SERVER['HTTP_USER_AGENT'],
            'tmpDir' => '/tmp/',
            'cookieFileName' => 'cookie.txt',
            'useCookieFile' => true,
        );

        $this->_url = $url;
        $this->setOptions($options);
    }

    /**
     * Setter for authentication
     *
     * @param bool $use
     */
    public function useAuth($use)
    {
        $this->authentication = 0;
        if ($use == true) {
            $this->authentication = 1;
        }
    }

    /**
     * Setter for postFields
     *
     * @param array $postFields
     */
    public function setPostFields($postFields)
    {
        $this->_post = true;
        $this->_postFields = $postFields;
    }

    /**
     * Setter for files. pass $_FILES here or similar structure
     *
     * @param array $files
     */
    public function setFiles($files)
    {
        $this->_files = $files;
    }

    /**
     * Setter for useragenet
     *
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->_useragent = $userAgent;
    }

    /**
     * Returns cookieFilename
     *
     * @return string
     */
    public function getCookieFileLocation()
    {
        return $this->_tmpDir . $this->_cookieFileName;
    }

    /**
     * Builds post data
     *
     * @param array $postData
     * @param array $output prepared data will be in this array
     * @param string $prefix
     */
    public function buildPostField($postData, &$output = array(), $prefix = null)
    {
        if (is_object($postData)) {
            $postData = get_object_vars($postData);
        }

        foreach ($postData AS $key => $value) {
            $k = isset($prefix) ? $prefix . '[' . $key . ']' : $key;
            if (is_array($value) || is_object($value)) {
                $this->buildPostField($value, $output, $k);
            }
            else {
                $output[$k] = $value;
            }
        }
    }

    /**
     * Formats the with post name structure so that it can be used in curl
     *
     * @param array $uploadedFiles uploaded files array which will be $_FILES
     * @return array
     */
    public function getFormattedFiles($uploadedFiles)
    {
        $formattedFilesData = array(
            'name' => array(),
            'tmp_name' => array(),
            'type' => array(),
            'size' => array(),
        );
        foreach ($uploadedFiles as $name => $fileData) {
            foreach (array_keys($formattedFilesData) as $key) {
                if (is_array($fileData[$key])) {
                    $postdata = array();
                    $this->buildPostField($uploadedFiles[$name][$key], $postdata, $name);
                    $formattedFilesData[$key] = array_merge($formattedFilesData[$key], $postdata);
                }
                else {
                    $formattedFilesData[$key][$name] = $fileData[$key];
                }
            }
        }

        // Format the array again so that it can be traversable by files
        $files = array();
        foreach ($formattedFilesData as $infoKey => $fileDataIndexedByPostname) {
            foreach ($fileDataIndexedByPostname as $postname => $valueForInforKey) {
                $files[$postname][$infoKey] = $valueForInforKey;
            }
        }

        // Remove Empty files data
        foreach (array_keys($files) as $idx) {
            if (empty($files[$idx]['name'])) {
                unset($files[$idx]);
            }
        }

        return $files;
    }

    /**
     * Exec to the url and get the contents from url
     *
     * @param string|null $url Updates the url and uses it
     * @return \IvvyEvent\CurlResponse
     */
    public function exec($url = null)
    {
        if ($url) {
            $this->_url = $url;
        }

        // Update the cookie file from local cookie
        $this->touchCookieFile();

        $s = curl_init();

        curl_setopt($s, CURLOPT_URL, $this->_url);
        curl_setopt($s, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($s, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($s, CURLOPT_MAXREDIRS, $this->_maxRedirects);
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_FOLLOWLOCATION, $this->_followlocation);
        curl_setopt($s, CURLOPT_VERBOSE, true);

        $verbose = fopen('php://temp', 'w+');
        curl_setopt($s, CURLOPT_STDERR, $verbose);

        if ($this->_useCookieFile) {
            curl_setopt($s, CURLOPT_COOKIEJAR, $this->getCookieFileLocation());
            curl_setopt($s, CURLOPT_COOKIEFILE, $this->getCookieFileLocation());
        }
        if ($this->_cookie) {
            curl_setopt($s, CURLOPT_COOKIE, $this->_cookie);
        }
        curl_setopt($s, CURLOPT_SSL_VERIFYPEER, !ENV_DEVELOPMENT);

        // Set multipart/form-data content type if its post request and has files
        if ($this->_post && $this->_files) {
            $this->_headers[] = "Content-type: multipart/form-data";
        }
        curl_setopt($s, CURLOPT_HTTPHEADER, $this->_headers);

        if ($this->authentication == 1) {
            curl_setopt($s, CURLOPT_USERPWD, $this->auth_name . ':' . $this->auth_pass);
        }
        if ($this->_post) {
            $postData = array();
            $this->buildPostField($this->_postFields, $postData);
            if ($this->_files && is_array($this->_files)) {
                $formattedFiles = $this->getFormattedFiles($this->_files);
                foreach ($formattedFiles as $postname => $uploadedFile) {
                    $postData[$postname] = curl_file_create(
                        $uploadedFile['tmp_name'],
                        $uploadedFile['type'],
                        $uploadedFile['name']
                    );
                }
            }
            curl_setopt($s, CURLOPT_POST, true);
            curl_setopt($s, CURLOPT_POSTFIELDS, $postData);
        }

        if ($this->_includeHeader) {
            curl_setopt($s, CURLOPT_HEADER, true);
        }

        if ($this->_noBody) {
            curl_setopt($s, CURLOPT_NOBODY, true);
        }

        if ($this->_binary) {
            curl_setopt($s, CURLOPT_BINARYTRANSFER, true);
        }

        curl_setopt($s, CURLOPT_USERAGENT, $this->_useragent);
        curl_setopt($s, CURLOPT_REFERER, $this->_referer);

        $response = new \IvvyEvent\CurlResponse($s, curl_exec($s));
        curl_close($s);

        // Update the cookie with update cookie file
        // Note: Cookie file is created after curl_easy_clean
        $this->touchCookie();

        return $response;
    }

    /**
     * Update cookie file from local cookie
     *
     * @return void
     */
    protected function touchCookieFile()
    {
        if (array_key_exists($this->cUrlCookieName, $_COOKIE)) {
            file_put_contents(
                $this->getCookieFileLocation(),
                base64_decode($_COOKIE[$this->cUrlCookieName])
            );
        }
    }

    /**
     * Sets local cookie content cookie file
     *
     * @return void
     */
    protected function touchCookie()
    {
        if (file_exists($this->getCookieFileLocation())) {
            setcookie(
                $this->cUrlCookieName,
                base64_encode(file_get_contents($this->getCookieFileLocation()))
            );
        }
    }

    /**
     * Sets default options of the Entity.
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options = array())
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            $protected = '_' . $key;
            // check for setter method
            if (method_exists($this, $method)) {
                // Setter exists; use it
                $this->$method($value);
            }
            // check for protected property
            else if (property_exists($this, $protected)) {
                $this->$protected = $value;
            }
            else if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Set one or more request headers
     *
     * @param array $headers
     * @return \IvvyEvent\CurlClient
     */
    public function setHeaders($headers)
    {
        $this->_headers = $headers;
        return $this;
    }
}
