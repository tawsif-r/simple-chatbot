<?php
class Simple_Chatbot_Loader {

    private $database_handler;
    private $admin_pages_handler;
    private $ajax_handler;
    private $frontend_pages_handler;

    public function __construct() {
        // Hook into WordPress initialization
        add_action('init', array($this, 'init'));

        // Register activation hook (must be in the main plugin file or referenced correctly)
        register_activation_hook( plugin_dir_path(__DIR__) . 'simple-chatbot.php', array($this, 'activate'));

        // Instantiate core components
        $this->database_handler = new Simple_Chatbot_Database();
        $this->admin_pages_handler = new Simple_Chatbot_Admin_Pages($this->database_handler);
        $this->ajax_handler = new Simple_Chatbot_Ajax_Handler($this->database_handler);
        $this->frontend_pages_handler = new Simple_Chatbot_Frontend($this->database_handler);
    }

    public function init() {
         // Any general initialization logic can go here if needed later
    }

    public function activate() {
        $this->database_handler->create_chat_table();
    }
}