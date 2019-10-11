<?php

/**
 * Plugin Name:     iVvy Events
 * Plugin URI:      https://www.ivvy.com.au
 * Description:     All the benefits of the iVvy registration tools, on your own Wordpress website.
 * Author:          iVvy
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Version:         1.0
 */

// This is default url if user has not setup the ivvy domain in settings page
define('IVVY_BASE_URL', 'https://www.ivvy.com.au');
define('ENV_DEVELOPMENT', false);

ini_set("display_errors", ENV_DEVELOPMENT);

// Debug functions
require_once 'src/IvvyEvent/debug.functions.php';

// If we haven't loaded this plugin from Composer we need to add our own autoloader
if (!class_exists('IvvyEvent\Init')) {
    // Get a reference to our PSR-4 Autoloader function that we can use to add our
    // Acme namespace
    $autoloader = require_once('autoload.php');

    // Use the autoload function to setup our class mapping
    $autoloader('IvvyEvent\\', __DIR__ . '/src/IvvyEvent/');
}

// We are now able to autoload classes under the IvvyEvent namespace so we
// can implement what ever functionality this plugin is supposed to have
\IvvyEvent\IvvyEvent::Instance()->run(__FILE__);
