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
 * Initialises Plugin and adds required hooks and filter
 *
 * @category   Event
 * @package    iVvy WordPress Project
 * @subpackage Event Registration
 * @copyright  Copyright (c) iVvy Pty Ltd
 */
final class IvvyEvent
{
    // Shortcode constants
    const SHORTCODE_REGISTRATION = 'IVVY_EVENT_REGISTRATION';
    const SHORTCODE_CALENDAR = 'IVVY_EVENT_CALENDAR';

    // Page constants
    const PAGE_REGISTRATION = 'ivvy_registration_page';
    const PAGE_CALENDAR = 'ivvy_calendar_page';

    // Request parameter constants
    const URL_PARAM_ACTION = 'action';
    const URL_PARAM_EVENTCODE = 'ecode';

    /**
     * Registration object which manages the requests and reponse for event registration
     *
     * @var \IvvyEvent\Registration
     */
    private $_registration = null;

    /**
     * Settings page class object which displays admin settings page
     *
     * @var \IvvyEvent\Settings
     */
    private $_settings = null;

    /**
     * Call this method to get singleton
     *
     * @return UserFactory
     */
    public static function Instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new self();
        }
        return $inst;
    }

    /**
     * Runs the plugin. Adds hooks and filters for WordPress
     *
     * @param $pluginMainFile Plugin Main file path with filename
     * @return void
     */
    public function run($pluginMainFile)
    {
        // Activation and deactivation hooks
        register_activation_hook($pluginMainFile, array($this, 'activate'));
        register_deactivation_hook($pluginMainFile, array($this, 'deactivate'));

        add_action('template_redirect', array($this, 'registration'));

        add_shortcode(self::SHORTCODE_CALENDAR, array($this, 'calendar_shortcode'));
        add_shortcode(self::SHORTCODE_REGISTRATION, array($this, 'registration_shortcode'));

        // Admin settings page related hooks
        $this->_settings = new \IvvyEvent\Settings();
        add_action('admin_init', array($this->_settings, 'init'));
        add_action('admin_menu', array($this->_settings, 'addIvvyEventRegSettingPage'));
    }

    /**
     * Initializes registration page
     *
     * @return array
     */
    public function registration()
    {
        // We only want to initialize Registration on registration page
        $currentPage = get_post();
        if ($currentPage->ID != self::getRegistrationPageId()) {
            return;
        }

        // Redirect user to home page as event code is not set
        if (!$this->getEventCode()) {
            wp_redirect(get_site_url());
            exit;
        }

        // Initialize the registration
        $this->_registration = new \IvvyEvent\Registration(
            $this->getIvvyEventUrl(),
            $this->getHttpClient()
        );
        $this->_registration->init();
    }

    /**
     * Function for calendar short code
     *
     * @return string
     */
    public function calendar_shortcode()
    {
        $calendar = new \IvvyEvent\Calendar();
        return $calendar->getCalendarIFrame(
            \IvvyEvent\Settings::getIvvyDomain(),
            \IvvyEvent\Settings::getFieldValue('accountDomain'),
            get_permalink(self::getRegistrationPageId())
        );
    }

    /**
     * Function for registration short code
     *
     * @return string
     */
    public function registration_shortcode()
    {
        if (!$this->_registration instanceof \IvvyEvent\Registration) {
            return '';
        }

        return '<div class="ivvy-widget">'
            . ($this->_registration->getResponse()
                ? $this->_registration->getResponse()->getBody()
                : '')
            . '</div>';
    }

    /**
     * Called when this plugin is activated
     *
     * @return void
     */
    public function activate()
    {
        foreach ($this->getDefaultPages() as $key => $page) {
            // Create new page for ivvy event registration
            $post_id = wp_insert_post(array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => 1,
                'post_title' => $page['post_title'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => $page['post_content'],
            ));
            // Store the post id in options to use it later
            update_option($key, $post_id);
        }
    }

    /**
     * Called when this plugin is deactivated
     *
     * @return void
     */
    public function deactivate()
    {
        // Remove the created pages
        foreach (array_keys($this->getDefaultPages()) as $key) {
            wp_delete_post(get_option($key));
        }
    }

    /**
     * Return wordpress page id on which registration will be displayed
     *
     * @return int
     */
    public function getRegistrationPageId()
    {
        return get_option(self::PAGE_REGISTRATION);
    }

    /**
     * Return wordpress calendar page id
     *
     * @return int
     */
    public function getCalendarPageId()
    {
        return get_option(self::PAGE_CALENDAR);
    }

    /**
     * Returns default pages of plugin
     *
     * @return array
     */
    public function getDefaultPages()
    {
        return array(
            self::PAGE_REGISTRATION => array(
                'post_title' => 'Event Registration',
                'post_content' => '[' . self::SHORTCODE_REGISTRATION . ']',
            ),
            self::PAGE_CALENDAR => array(
                'post_title' => 'Event Calendar',
                'post_content' => '[' . self::SHORTCODE_CALENDAR . ']',
            ),
        );
    }

    /**
     * Returns the wordpress registration page url
     *
     * @return string
     */
    public function getRegistrationPageUrl()
    {
        return \IvvyEvent\Util::buildUrl(
            get_permalink($this->getRegistrationPageId()),
            array(
                self::URL_PARAM_EVENTCODE => \IvvyEvent\Util::getRequestParam(self::URL_PARAM_EVENTCODE)
            )
        );
    }

    /**
     * Return Ivvy event website url
     *
     * @return string
     */
    public function getIvvyEventUrl()
    {
        return rtrim(\IvvyEvent\Settings::getIvvyDomain(), '/')
            . '/event/' . $this->getEventCode();
    }

    /**
     * Return event code request
     *
     * @return string
     */
    public function getEventCode()
    {
        return \IvvyEvent\Util::getRequestParam(self::URL_PARAM_EVENTCODE);
    }

    /**
     * Returns curl client
     *
     * @param string $url
     * @param array $options
     * @return \IvvyEvent\CurlClient
     */
    public function getHttpClient()
    {
        $this->_setupSession();
        $client = new \IvvyEvent\CurlClient(null, array(
            'tmpDir' => get_temp_dir(),
            'cookieFileName' => session_id() . '.txt',
        ));
        $client->setHeaders(array(
            // iVvy identifies this request using this param as third party request
            'X_REQUESTED_WITH: ' . \IvvyEvent\Registration::X_REQUESTED_WITH,

            // Sends the url for wp registration page
            'X_TP_PAGEURL: ' . $this->getRegistrationPageUrl(),
        ));
        return $client;
    }

    /**
     * Setup Session. We need session id to uniquely identify each request
     *
     * @return void
     */
    private function _setupSession()
    {
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Private construct for singleton class
     */
    private function __construct()
    {}
}