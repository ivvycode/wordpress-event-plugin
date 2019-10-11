<?php

/**
 * Debug function and break
 */
function srd()
{
    $args = func_get_args();
    echo "<pre>";
    foreach ($args as $arg) {
        print_r($arg);
    }
    $debug = debug_backtrace();
    echo "</pre>";
    $text = '';
    foreach ($debug as $d) {
        if (isset($d['file'])) {
            $text = $d['file'] . '--' . $d['line'];
            break;
        }
    }
    echo $text;
    exit;
}

/**
 * Debug function
 */
function sr()
{
    $args = func_get_args();
    echo "<pre>";
    foreach ($args as $arg) {
        print_r($arg);
    }
    echo "</pre>";
    $debug = debug_backtrace();
    $text = '';
    foreach ($debug as $d) {
        if (isset($d['file'])) {
            $text = $d['file'] . '--' . $d['line'];
            break;
        }
    }
    echo $text;
}

/**
 * Adds log in file for debugging while development
 *
 * @param string $message
 */
function ivvylog($message)
{
    if (ENV_DEVELOPMENT) {
        $filename = __DIR__ . "/request.log";
        file_put_contents($filename, date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND);
    }
}
