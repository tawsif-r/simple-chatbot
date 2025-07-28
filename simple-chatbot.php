<?php
/**
 * @package Simple_Chatbot
 * @version 1.0.0
 */
/*
Plugin Name: Simple Chatbot
Plugin URI: http://wordpress.org/plugins/simple-chatbot/
Description: A simple chatbot plugin that integrates with Hugging Face AI for WordPress admin dashboard.
Author: Tawsif
Version: 1.0.0
*/

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
    die();
}

// Define plugin path constant for easier file inclusion
if (!defined('SIMPLE_CHATBOT_PLUGIN_DIR')) {
    define('SIMPLE_CHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Autoload classes or manually include necessary files
// For simplicity, we'll include them directly here.
// A more advanced approach would use an autoloader.

require_once SIMPLE_CHATBOT_PLUGIN_DIR . 'includes/class-simple-chatbot-loader.php';
require_once SIMPLE_CHATBOT_PLUGIN_DIR . 'includes/class-simple-chatbot-database.php';
require_once SIMPLE_CHATBOT_PLUGIN_DIR . 'includes/class-simple-chatbot-admin-pages.php';
require_once SIMPLE_CHATBOT_PLUGIN_DIR . 'includes/class-simple-chatbot-ajax-handler.php';
require_once SIMPLE_CHATBOT_PLUGIN_DIR . 'includes/class-simple-chatbot-api-client.php';

// Initialize the main plugin class
new Simple_Chatbot_Loader();