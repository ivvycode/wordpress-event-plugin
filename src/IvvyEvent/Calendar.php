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
class Calendar
{
    /**
     * Returns IFrame html for calendar
     *
     * @param string $ivvyHostUrl
     * @param string $accountDomain
     * @param string $registrationPageUrl
     * @return string
     */
    public function getCalendarIFrame($ivvyHostUrl, $accountDomain, $registrationPageUrl)
    {
        return sprintf('<iframe src="%1$s/g/%3$s/event/calendar?tpUrl=%2$s" '
                . 'frameborder="0" width="100%%" height="700">'
                . '<p>Your browser does not support iframes.</p>'
                . '</iframe>',
            $ivvyHostUrl,           // 1
            $registrationPageUrl,   // 2
            $accountDomain          // 3
        );
    }
}