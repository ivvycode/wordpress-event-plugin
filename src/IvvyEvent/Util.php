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
 * Class for utility functions
 *
 * @category   Event
 * @package    iVvy WordPress Project
 * @subpackage Event Registration
 * @copyright  Copyright (c) iVvy Pty Ltd
 */
class Util
{
    /**
     * Builds url using params
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function buildUrl($url, $params)
    {
        $buildUrl = $url . ((strpos($url, '?') === false) ? '?' : '&');
        $buildUrl .= http_build_query($params);
        return $buildUrl;
    }

    /**
     * Checks whether string is url
     *
     * @param string $url
     * @return boolean
     */
    public static function isUrl($url)
    {
        if (empty($url)) {
            return false;
        }
        return (boolean) filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Retrieve the request parameter from $_GET and $_POST
     *
     * @param mixed $key
     * @param mixed $default Default value to use if key not found
     * @return mixed
     */
    public static function getRequestParam($key, $default = null)
    {
        $paramSources = array('_GET', '_POST');
        if (in_array('_GET', $paramSources) && (isset($_GET[$key]))) {
            return $_GET[$key];
        }
        else if (in_array('_POST', $paramSources) && (isset($_POST[$key]))) {
            return $_POST[$key];
        }

        return $default;
    }

    /**
     * Returns $_POST data
     *
     * @return array
     */
    public static function getPost()
    {
        return $_POST;
    }

    /**
     * Returns $_FILES data
     *
     * @return array
     */
    public static function getPostFiles()
    {
        return $_FILES;
    }
}
