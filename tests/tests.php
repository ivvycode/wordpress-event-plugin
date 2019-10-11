<?php

// Load the WordPress library.
require_once( __DIR__ . '/../../../../wp-load.php' );

// Set up the WordPress query.
wp();

// Load the theme template.
require_once( ABSPATH . WPINC . '/template-loader.php' );

// Load Plugin
require_once __DIR__ .  '/../ivvy-event.php';