<?php

namespace IvvyEvent;
use IvvyEvent\Util;
use IvvyEvent\IvvyEvent;

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
 * Registration class for handling steps and step requests
 *
 * @category   Event
 * @package    iVvy WordPress Project
 * @subpackage Event Registration
 * @copyright  Copyright (c) iVvy Pty Ltd
 */
class Registration
{
    // Registration steps endpoint on ivvy
    const REG_STEPS_ENDPOINT = '/register/steps';

    // Cookie name for maintaining session
    const COOKIE_NAME = 'ivsid';

    // Constant for setting header X_REQUESTED_WITH header
    const X_REQUESTED_WITH = 'ivvyEventWebsitePlugin';

    /**
     * iVvy Event URL
     *
     * @var string
     */
    private $_ivvyEventUrl = null;

    /**
     * Third Party registration page URL to send it to iVvy in header
     *
     * @var string
     */
    private $_registerPageUrl = null;

    /**
     * Http Client
     *
     * @var \IvvyEvent\CurlClient
     */
    private $_client = null;

    /**
     * Array of steps
     *
     * @var array
     */
    private $_steps = array();

    /**
     * Curl Response, this will be set by processing request
     *
     * @var \IvvyEvent\CurlResponse
     */
    private $_response = '';

    /**
     * Constructor.
     *
     * @param string $ivvyEventUrl
     * @param \IvvyEvent\CurlClient $client
     * @param array $options
     */
    public function __construct($ivvyEventUrl, $client, $options = array())
    {
        if (!$client instanceof \IvvyEvent\CurlClient
                || !Util::isUrl($ivvyEventUrl)) {
            throw new \Exception('Invalid Usage of class');
        }
        $this->_client = $client;
        $this->_ivvyEventUrl = $ivvyEventUrl;

        $this->setOptions($options);
    }

    /**
     * Initialize the registration.
     *
     * @return void
     */
    public function init()
    {
        $this->getSteps();
        $this->processCurrentStep();
    }

    /**
     * Requests the page from iVvy and redirect the url if its redirect response.
     *
     * @return void
     */
    public function processCurrentStep()
    {
        $registrationClient = $this->_client;

        ivvylog("Prepare Request");
        // Set Post data in request, if request has post data
        if (Util::getPost()) {
            ivvylog("Setting Post Data");
            $registrationClient->setPostFields(Util::getPost());
        }

        // Set Files in request is has files
        if (Util::getPostFiles()) {
            ivvylog("Setting Post Files");
            $registrationClient->setFiles(Util::getPostFiles());
        }

        ivvylog("Hitting");
        // Send Request and store response
        $this->_response = $registrationClient->exec($this->getIvvyCurrentStepUrl());

        // Redirect user if it is redirect response, ivvy has prepared url for us :)
        if ($this->_response->isRedirectResponse()) {
            ivvylog("Got Redirect Response : " . $this->_response->getRedirectLocation());
            header('Location: ' . $this->_response->getRedirectLocation());
            exit;
        }
        ivvylog("Got Normal Response");
    }

    /**
     * Send request to event and fetches steps
     *
     * @return array
     */
    public function getSteps()
    {
        // Return, if steps are already set
        if (!$this->_steps) {
            // Send Request
            $response = $this->_client->exec($this->getStepsUrl());

            // Prcess the response
            if ($response->isJsonResponse()) {
                $this->_steps = json_decode($response->getBody(), true);
            }
        }

        return $this->_steps;
    }

    /**
     * Returns current steps action
     *
     * @return string
     */
    public function getIvvyCurrentStepUrl()
    {
        if (!$this->_steps) {
            $this->getSteps();
        }
        $action = trim(urldecode(
            Util::getRequestParam(IvvyEvent::URL_PARAM_ACTION)
        ), '/');

        if ($action && Util::isUrl($action)) {
            return $action;
        }

        if (!$action) {
            $step = $this->getFirstStep();
            $action = $step['action'];
        }

        return $this->_ivvyEventUrl . '/' . ltrim($action, '/');
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
     * Returns the response
     *
     * @return \IvvyEvent\CurlResponse
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Returns Ivvy registration steps url
     *
     * @return string
     */
    public function getStepsUrl()
    {
        return $this->_ivvyEventUrl . self::REG_STEPS_ENDPOINT;
    }

    /**
     * Return data of first step
     *
     * @return array
     */
    public function getFirstStep()
    {
        if (!is_array($this->_steps)) {
            return null;
        }
        reset($this->_steps);
        $firstKey = key($this->_steps);
        return $this->_steps[$firstKey];
    }
}