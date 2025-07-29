## Backup code base
```php
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

class SimpleChatbot {
    
    public function __construct() {
        add_action( 'admin_menu', array($this, 'hd_add_admin_menu') );
        add_action( 'wp_ajax_send_chat_message', array($this, 'handle_chat_message') );
        add_action( 'admin_head', array($this, 'add_admin_styles') );
        add_action( 'admin_footer', array($this, 'add_admin_scripts') );
        
        // Create database table on activation
        // This function registers a callback that runs only once when your plugin is first activated by a user. It's perfect for one-time setup tasks.
        register_activation_hook( __FILE__, array($this, 'create_chat_table') );
    }
    
    public function create_chat_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'simple_chatbot_messages';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            message text NOT NULL,
            response text NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    

    public function add_admin_styles() {
        // Only load on our plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'simple-chatbot') === false) {
            return;
        }
        ?>
        <style>
        .chat-message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            line-height: 1.5;
            max-width: 90%;
            word-wrap: break-word;
            animation: fadeInUp 0.3s ease-out;
        }
        .user-message {
            background-color: #e3f2fd;
            margin-left: 20px;
            border-bottom-right-radius: 4px;
            border: 1px solid #bbdefb;
        }
        .bot-message {
            background-color: #f5f5f5;
            margin-right: 20px;
            border-bottom-left-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .bot-message.error {
            background-color: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }
        #chat-container {
            height: 400px;
            overflow-y: auto;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        #chat-container::-webkit-scrollbar {
            width: 8px;
        }
        #chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        #chat-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        #chat-input {
            border: 2px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        #chat-input:focus {
            border-color: #0073aa;
            outline: none;
            box-shadow: 0 0 0 1px #0073aa;
        }
        #send-button {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        #send-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        #send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        #loading {
            color: #666;
            font-style: italic;
            padding: 5px 0;
        }
        .chat-entry {
            background: #fff;
            border-left: 4px solid #0073aa;
            transition: box-shadow 0.3s ease;
        }
        .chat-entry:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .timestamp {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 768px) {
            .chat-message {
                max-width: 95%;
                padding: 10px;
                font-size: 14px;
            }
            #chat-container {
                height: 300px;
                padding: 10px;
            }
            #chat-input {
                font-size: 16px;
            }
        }
        </style>
        <?php
    }
    
    public function add_admin_scripts() {
        // Only load on our plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'simple-chatbot') === false) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Simple Chatbot: Script loaded');
            
            const chatMessages = $('#chat-messages');
            const chatInput = $('#chat-input');
            const sendButton = $('#send-button');
            const loading = $('#loading');
            const chatContainer = $('#chat-container');
            
            console.log('Elements found:', {
                chatMessages: chatMessages.length,
                chatInput: chatInput.length,
                sendButton: sendButton.length,
                loading: loading.length,
                chatContainer: chatContainer.length
            });
            
            // Send message function
            function sendMessage() {
                console.log('Send message triggered');
                const message = chatInput.val().trim();
                
                if (!message) {
                    console.log('Empty message, returning');
                    return;
                }
                
                console.log('Sending message:', message);
                
                // Add user message to chat
                addMessageToChat(message, 'user');
                
                // Clear input and disable controls
                chatInput.val('');
                chatInput.prop('disabled', true);
                sendButton.prop('disabled', true);
                loading.show();
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'send_chat_message',
                        message: message,
                        nonce: '<?php echo wp_create_nonce('simple_chatbot_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('AJAX Success:', response);
                        if (response.success) {
                            addMessageToChat(response.data, 'bot');
                        } else {
                            addMessageToChat('Error: ' + response.data, 'bot error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr.responseText, status, error);
                        addMessageToChat('Connection error: ' + error, 'bot error');
                    },
                    complete: function() {
                        console.log('AJAX Complete');
                        // Re-enable controls
                        chatInput.prop('disabled', false);
                        sendButton.prop('disabled', false);
                        loading.hide();
                        chatInput.focus();
                    }
                });
            }
            
            // Add message to chat display
            function addMessageToChat(message, type) {
                const messageClass = type === 'user' ? 'user-message' : 'bot-message';
                const sender = type === 'user' ? 'You' : 'Bot';
                const errorClass = type.includes('error') ? ' error' : '';
                
                const messageHtml = '<div class="chat-message ' + messageClass + errorClass + '"><strong>' + sender + ':</strong> ' + escapeHtml(message) + '</div>';
                
                chatMessages.append(messageHtml);
                
                // Scroll to bottom
                chatContainer.scrollTop(chatContainer[0].scrollHeight);
            }
            
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }
            
            // Event listeners
            sendButton.on('click', function(e) {
                e.preventDefault();
                console.log('Send button clicked');
                sendMessage();
            });
            
            chatInput.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) { // Enter key
                    e.preventDefault();
                    console.log('Enter key pressed');
                    sendMessage();
                }
            });
            
            // Focus on input when page loads
            chatInput.focus();
        });
        </script>
        <?php
    }
    // Adding all the menu items here
    public function hd_add_admin_menu() {
        // Add top-level menu page
        add_menu_page(
            'Simple Chatbot Dashboard', // Page title
            'Simple Chatbot', // Menu title
            'manage_options', // Capability
            'simple-chatbot-main', // Menu slug
            array($this, 'main_page'), // Callback function
            'dashicons-format-quote', // Icon
            30 // Position
        );
        
        // Add submenu pages under the main menu
        add_submenu_page(
            'simple-chatbot-main', // Parent slug
            'Settings', // Page title
            'Settings', // Menu title
            'manage_options', // Capability
            'simple-chatbot-settings', // Menu slug
            array($this, 'settings_page') // Callback function
        );
        
        add_submenu_page(
            'simple-chatbot-main', // Parent slug
            'Chat History', // Page title
            'Chat History', // Menu title
            'manage_options', // Capability
            'simple-chatbot-history', // Menu slug
            array($this, 'history_page') // Callback function
        );
    }
    
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'simple_chatbot_settings')) {
            update_option('simple_chatbot_hf_token', sanitize_text_field($_POST['hf_token']));
            update_option('simple_chatbot_model', sanitize_text_field($_POST['model']));
            update_option('simple_chatbot_system_message', sanitize_textarea_field($_POST['system_message']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $hf_token = get_option('simple_chatbot_hf_token', '');
        $model = get_option('simple_chatbot_model', 'deepseek-ai/DeepSeek-R1:novita');
        $system_message = get_option('simple_chatbot_system_message', 'You are a helpful assistant.');
        ?>
        <div class="wrap">
            <h1>Simple Chatbot Settings</h1>
            <form method="post">
                <?php wp_nonce_field('simple_chatbot_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Hugging Face Token</th>
                        <td>
                            <input type="password" name="hf_token" value="<?php echo esc_attr($hf_token); ?>" class="regular-text" />
                            <p class="description">Enter your Hugging Face API token. Get one from <a href="https://huggingface.co/settings/tokens" target="_blank">https://huggingface.co/settings/tokens</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <select name="model" class="regular-text">
                                <option value="deepseek-ai/DeepSeek-R1:novita" <?php selected($model, 'deepseek-ai/DeepSeek-R1:novita'); ?>>DeepSeek R1 (Novita)</option>
                                <option value="microsoft/DialoGPT-large" <?php selected($model, 'microsoft/DialoGPT-large'); ?>>DialoGPT Large</option>
                                <option value="microsoft/DialoGPT-medium" <?php selected($model, 'microsoft/DialoGPT-medium'); ?>>DialoGPT Medium</option>
                                <option value="facebook/blenderbot-400M-distill" <?php selected($model, 'facebook/blenderbot-400M-distill'); ?>>BlenderBot 400M</option>
                                <option value="custom" <?php selected($model, 'custom'); ?>>Custom Model</option>
                            </select>
                            <p class="description">Select the Hugging Face model to use for chat completions.</p>
                            <div id="custom-model" style="margin-top: 10px; <?php echo $model !== 'custom' ? 'display: none;' : ''; ?>">
                                <input type="text" name="custom_model" value="<?php echo esc_attr(get_option('simple_chatbot_custom_model', '')); ?>" class="regular-text" placeholder="Enter custom model name" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">System Message</th>
                        <td>
                            <textarea name="system_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea($system_message); ?></textarea>
                            <p class="description">This message sets the behavior of the AI assistant.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('select[name="model"]').change(function() {
                    if ($(this).val() === 'custom') {
                        $('#custom-model').show();
                    } else {
                        $('#custom-model').hide();
                    }
                });
            });
            </script>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h3>Debug Information</h3>
                <p><strong>HF Token Status:</strong> <?php echo !empty($hf_token) ? 'Configured (' . strlen($hf_token) . ' characters)' : 'Not configured'; ?></p>
                <p><strong>Selected Model:</strong> <?php echo esc_html($model); ?></p>
                <p><strong>WordPress AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
                <p><strong>Current User Can Manage Options:</strong> <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></p>
                <p><strong>Database Table Exists:</strong> 
                    <?php 
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'simple_chatbot_messages';
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                    echo $table_exists ? 'Yes' : 'No';
                    ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    public function history_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chatbot_messages';
        
        $messages = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 50");
        ?>
        <div class="wrap">
            <h1>Chat History</h1>
            <div class="chat-history">
                <?php if (empty($messages)): ?>
                    <p>No chat history found.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="chat-entry" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                            <div class="timestamp" style="font-size: 12px; color: #666; margin-bottom: 10px;">
                                <?php echo esc_html($msg->timestamp); ?>
                            </div>
                            <div class="user-message" style="margin-bottom: 10px;">
                                <strong>User:</strong> <?php echo esc_html($msg->message); ?>
                            </div>
                            <div class="bot-response">
                                <strong>Bot:</strong> <?php echo esc_html($msg->response); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function main_page() {
        $hf_token = get_option('simple_chatbot_hf_token', '');
        ?>
        <div class="wrap">
            <h1>Simple Chatbot Dashboard</h1>
            
            <?php if (empty($hf_token)): ?>
                <div class="notice notice-warning">
                    <p>Please configure your Hugging Face token in <a href="<?php echo admin_url('admin.php?page=simple-chatbot-settings'); ?>">Settings</a> to use the chatbot.</p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px;">
                <h2>Hugging Face AI Chat</h2>
                <div id="chat-container">
                    <div id="chat-messages">
                        <div class="chat-message bot-message">
                            <strong>Bot:</strong> Hello! I'm your AI assistant powered by Hugging Face. How can I help you today?
                        </div>
                    </div>
                </div>
                
                <div id="chat-input-container">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="chat-input" placeholder="Type your message here..." style="flex: 1; padding: 8px;" <?php echo empty($hf_token) ? 'disabled' : ''; ?> />
                        <button id="send-button" class="button button-primary" <?php echo empty($hf_token) ? 'disabled' : ''; ?>>Send</button>
                    </div>
                    <div id="loading" style="display: none; margin-top: 10px;">
                        <em>Bot is thinking...</em>
                    </div>
                </div>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3>Quick Actions</h3>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=simple-chatbot-settings'); ?>" class="button button-primary">
                        Plugin Settings
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=simple-chatbot-history'); ?>" class="button button-secondary" style="margin-left: 10px;">
                        View Chat History
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    public function handle_chat_message() {
        // Add debug logging
        error_log('Simple Chatbot: handle_chat_message called');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Check if user is logged in and has permissions
        if (!current_user_can('manage_options')) {
            error_log('Simple Chatbot: User does not have permissions');
            wp_send_json_error('Permission denied');
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simple_chatbot_nonce')) {
            error_log('Simple Chatbot: Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!isset($_POST['message'])) {
            error_log('Simple Chatbot: No message in POST data');
            wp_send_json_error('No message provided');
            return;
        }
        
        $message = sanitize_text_field($_POST['message']);
        $hf_token = get_option('simple_chatbot_hf_token', '');
        $model = get_option('simple_chatbot_model', 'deepseek-ai/DeepSeek-R1:novita');
        $system_message = get_option('simple_chatbot_system_message', 'You are a helpful assistant.');
        
        // Handle custom model
        if ($model === 'custom') {
            $model = get_option('simple_chatbot_custom_model', 'deepseek-ai/DeepSeek-R1:novita');
        }
        
        error_log('Simple Chatbot: Message: ' . $message);
        error_log('Simple Chatbot: HF Token length: ' . strlen($hf_token));
        error_log('Simple Chatbot: Model: ' . $model);
        
        if (empty($hf_token)) {
            error_log('Simple Chatbot: HF token not configured');
            wp_send_json_error('Hugging Face token not configured');
            return;
        }
        
        // Make API call to Hugging Face
        $response = $this->call_huggingface_api($message, $hf_token, $model, $system_message);
        
        if (is_wp_error($response)) {
            error_log('Simple Chatbot: API Error: ' . $response->get_error_message());
            wp_send_json_error($response->get_error_message());
            return;
        }
        
        error_log('Simple Chatbot: API Response: ' . $response);
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chatbot_messages';
        $result = $wpdb->insert(
            $table_name,
            array(
                'message' => $message,
                'response' => $response
            )
        );
        
        if ($result === false) {
            error_log('Simple Chatbot: Database insert failed: ' . $wpdb->last_error);
        }
        
        wp_send_json_success($response);
    }
    
    private function call_huggingface_api($message, $hf_token, $model, $system_message) {
        error_log('Simple Chatbot: Making API call to Hugging Face');
        
        $url = 'https://router.huggingface.co/v1/chat/completions';
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_message
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => 1000,
            'temperature' => 0.7
        );
        
        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $hf_token
            ),
            'timeout' => 300,
            'method' => 'POST'
        );
        
        error_log('Simple Chatbot: API Request body: ' . json_encode($body));
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Simple Chatbot: wp_remote_post error: ' . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('Simple Chatbot: API Response code: ' . $response_code);
        error_log('Simple Chatbot: API Response body: ' . $body);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API returned status code: ' . $response_code . ' - ' . $body);
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('api_error', 'Invalid JSON response from API');
        }
        
        if (isset($data['error'])) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            return new WP_Error('api_error', $error_message);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Invalid response structure from API');
        }
        
        return $data['choices'][0]['message']['content'];
    }
}

new SimpleChatbot();
```


## After dividing into multiple file system
```php
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

```


## This one is with the frontend included
```php
<?php
/**
 * @package Simple_Chatbot
 * @version 2.0.0
 */
/*
Plugin Name: Simple Chatbot Frontend
Plugin URI: http://wordpress.org/plugins/simple-chatbot/
Description: A simple chatbot plugin that integrates with Hugging Face AI for both WordPress admin dashboard and frontend.
Author: Tawsif
Version: 2.0.0
*/

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
    die();
}

class SimpleChatbot {
    
    public function __construct() {
        // Admin hooks
        add_action( 'admin_menu', array($this, 'hd_add_admin_menu') );
        add_action( 'admin_head', array($this, 'add_admin_styles') );
        add_action( 'admin_footer', array($this, 'add_admin_scripts') );
        
        // Frontend hooks
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_frontend_assets') );
        add_action( 'wp_footer', array($this, 'add_frontend_chatbot') );
        
        // AJAX hooks (both logged in and logged out users)
        add_action( 'wp_ajax_send_chat_message', array($this, 'handle_chat_message') );
        add_action( 'wp_ajax_nopriv_send_chat_message', array($this, 'handle_chat_message') );
        
        // Shortcode for embedding chatbot
        add_shortcode( 'simple_chatbot', array($this, 'chatbot_shortcode') );
        
        // Create database table on activation
        register_activation_hook( __FILE__, array($this, 'create_chat_table') );
    }
    
    public function create_chat_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'simple_chatbot_messages';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            message text NOT NULL,
            response text NOT NULL,
            user_ip varchar(45),
            user_agent text,
            is_admin tinyint(1) DEFAULT 0,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    // Frontend Assets
    public function enqueue_frontend_assets() {
        // Only load if chatbot is enabled for frontend
        if (!get_option('simple_chatbot_enable_frontend', 1)) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'simple_chatbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simple_chatbot_nonce')
        ));
    }
    
    // Frontend Chatbot Widget
    public function add_frontend_chatbot() {
        // Only show if enabled and not in admin
        if (!get_option('simple_chatbot_enable_frontend', 1) || is_admin()) {
            return;
        }
        
        $position = get_option('simple_chatbot_position', 'bottom-right');
        $widget_title = get_option('simple_chatbot_widget_title', 'Chat with us!');
        $placeholder = get_option('simple_chatbot_placeholder', 'Type your message...');
        ?>
        <div id="simple-chatbot-widget" class="chatbot-<?php echo esc_attr($position); ?>">
            <div id="chatbot-toggle">
                <span class="chatbot-icon">💬</span>
                <span class="chatbot-close">✕</span>
            </div>
            <div id="chatbot-container">
                <div id="chatbot-header">
                    <h4><?php echo esc_html($widget_title); ?></h4>
                    <button id="chatbot-minimize">−</button>
                </div>
                <div id="chatbot-messages">
                    <div class="bot-message">
                        <strong>Bot:</strong> Hello! How can I help you today?
                    </div>
                </div>
                <div id="chatbot-input-area">
                    <input type="text" id="chatbot-input" placeholder="<?php echo esc_attr($placeholder); ?>" />
                    <button id="chatbot-send">Send</button>
                </div>
                <div id="chatbot-loading" style="display: none;">
                    <em>Bot is typing...</em>
                </div>
            </div>
        </div>
        
        <style>
        #simple-chatbot-widget {
            position: fixed;
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .chatbot-bottom-right {
            bottom: 20px;
            right: 20px;
        }
        
        .chatbot-bottom-left {
            bottom: 20px;
            left: 20px;
        }
        
        .chatbot-top-right {
            top: 20px;
            right: 20px;
        }
        
        .chatbot-top-left {
            top: 20px;
            left: 20px;
        }
        
        #chatbot-toggle {
            width: 60px;
            height: 60px;
            background: #0073aa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        #chatbot-toggle:hover {
            transform: scale(1.1);
            background: #005a87;
        }
        
        #chatbot-toggle .chatbot-icon {
            font-size: 24px;
            color: white;
        }
        
        #chatbot-toggle .chatbot-close {
            font-size: 20px;
            color: white;
            display: none;
        }
        
        #chatbot-toggle.active .chatbot-icon {
            display: none;
        }
        
        #chatbot-toggle.active .chatbot-close {
            display: block;
        }
        
        #chatbot-container {
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        #chatbot-header {
            background: #0073aa;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        #chatbot-header h4 {
            margin: 0;
            font-size: 16px;
        }
        
        #chatbot-minimize {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
        }
        
        #chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            max-height: 350px;
        }
        
        #chatbot-messages::-webkit-scrollbar {
            width: 6px;
        }
        
        #chatbot-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        #chatbot-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .user-message, .bot-message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 85%;
            word-wrap: break-word;
            animation: fadeInUp 0.3s ease;
        }
        
        .user-message {
            background: #0073aa;
            color: white;
            margin-left: auto;
            text-align: right;
        }
        
        .bot-message {
            background: #f5f5f5;
            color: #333;
            margin-right: auto;
        }
        
        .bot-message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #f44336;
        }
        
        #chatbot-input-area {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        
        #chatbot-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
        }
        
        #chatbot-input:focus {
            border-color: #0073aa;
        }
        
        #chatbot-send {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        #chatbot-send:hover:not(:disabled) {
            background: #005a87;
        }
        
        #chatbot-send:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        #chatbot-loading {
            padding: 10px 20px;
            color: #666;
            font-style: italic;
            text-align: center;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 480px) {
            #chatbot-container {
                width: calc(100vw - 40px);
                height: calc(100vh - 100px);
                position: fixed;
                bottom: 80px;
                left: 20px;
                right: 20px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            const widget = $('#simple-chatbot-widget');
            const toggle = $('#chatbot-toggle');
            const container = $('#chatbot-container');
            const messages = $('#chatbot-messages');
            const input = $('#chatbot-input');
            const sendBtn = $('#chatbot-send');
            const loading = $('#chatbot-loading');
            const minimize = $('#chatbot-minimize');
            
            let isOpen = false;
            
            // Toggle chatbot
            toggle.on('click', function() {
                isOpen = !isOpen;
                if (isOpen) {
                    container.show();
                    toggle.addClass('active');
                    input.focus();
                } else {
                    container.hide();
                    toggle.removeClass('active');
                }
            });
            
            // Minimize chatbot
            minimize.on('click', function() {
                container.hide();
                toggle.removeClass('active');
                isOpen = false;
            });
            
            // Send message function
            function sendMessage() {
                const message = input.val().trim();
                if (!message) return;
                
                // Add user message
                addMessage(message, 'user');
                input.val('');
                
                // Disable input
                input.prop('disabled', true);
                sendBtn.prop('disabled', true);
                loading.show();
                
                // Send AJAX request
                $.ajax({
                    url: simple_chatbot_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'send_chat_message',
                        message: message,
                        nonce: simple_chatbot_ajax.nonce,
                        is_frontend: 1
                    },
                    success: function(response) {
                        if (response.success) {
                            addMessage(response.data, 'bot');
                        } else {
                            addMessage('Error: ' + response.data, 'bot error');
                        }
                    },
                    error: function() {
                        addMessage('Connection error. Please try again.', 'bot error');
                    },
                    complete: function() {
                        input.prop('disabled', false);
                        sendBtn.prop('disabled', false);
                        loading.hide();
                        input.focus();
                    }
                });
            }
            
            // Add message to chat
            function addMessage(text, type) {
                const messageClass = type.includes('user') ? 'user-message' : 'bot-message';
                const errorClass = type.includes('error') ? ' error' : '';
                const sender = type.includes('user') ? 'You' : 'Bot';
                
                const messageHtml = '<div class="' + messageClass + errorClass + '"><strong>' + sender + ':</strong> ' + escapeHtml(text) + '</div>';
                messages.append(messageHtml);
                messages.scrollTop(messages[0].scrollHeight);
            }
            
            // Escape HTML
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }
            
            // Event listeners
            sendBtn.on('click', sendMessage);
            input.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });
        </script>
        <?php
    }
    
    // Shortcode for embedding chatbot in posts/pages
    public function chatbot_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '400px',
            'width' => '100%',
            'title' => 'Chat Assistant'
        ), $atts);
        
        ob_start();
        ?>
        <div class="simple-chatbot-embed" style="width: <?php echo esc_attr($atts['width']); ?>; max-width: 100%;">
            <div class="chatbot-embed-header">
                <h4><?php echo esc_html($atts['title']); ?></h4>
            </div>
            <div class="chatbot-embed-messages" style="height: <?php echo esc_attr($atts['height']); ?>;">
                <div class="bot-message">
                    <strong>Bot:</strong> Hello! How can I help you today?
                </div>
            </div>
            <div class="chatbot-embed-input">
                <input type="text" class="chatbot-embed-input-field" placeholder="Type your message..." />
                <button class="chatbot-embed-send">Send</button>
            </div>
            <div class="chatbot-embed-loading" style="display: none;">
                <em>Bot is typing...</em>
            </div>
        </div>
        
        <style>
        .simple-chatbot-embed {
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .chatbot-embed-header {
            background: #0073aa;
            color: white;
            padding: 15px;
        }
        
        .chatbot-embed-header h4 {
            margin: 0;
        }
        
        .chatbot-embed-messages {
            padding: 20px;
            overflow-y: auto;
            border-bottom: 1px solid #eee;
        }
        
        .chatbot-embed-input {
            padding: 15px;
            display: flex;
            gap: 10px;
        }
        
        .chatbot-embed-input-field {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .chatbot-embed-send {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .chatbot-embed-loading {
            padding: 10px 20px;
            color: #666;
            font-style: italic;
            text-align: center;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.simple-chatbot-embed').each(function() {
                const embed = $(this);
                const messages = embed.find('.chatbot-embed-messages');
                const input = embed.find('.chatbot-embed-input-field');
                const sendBtn = embed.find('.chatbot-embed-send');
                const loading = embed.find('.chatbot-embed-loading');
                
                function sendEmbedMessage() {
                    const message = input.val().trim();
                    if (!message) return;
                    
                    // Add user message
                    addEmbedMessage(message, 'user');
                    input.val('');
                    
                    // Disable input
                    input.prop('disabled', true);
                    sendBtn.prop('disabled', true);
                    loading.show();
                    
                    // Send AJAX request
                    $.ajax({
                        url: simple_chatbot_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'send_chat_message',
                            message: message,
                            nonce: simple_chatbot_ajax.nonce,
                            is_frontend: 1
                        },
                        success: function(response) {
                            if (response.success) {
                                addEmbedMessage(response.data, 'bot');
                            } else {
                                addEmbedMessage('Error: ' + response.data, 'bot error');
                            }
                        },
                        error: function() {
                            addEmbedMessage('Connection error. Please try again.', 'bot error');
                        },
                        complete: function() {
                            input.prop('disabled', false);
                            sendBtn.prop('disabled', false);
                            loading.hide();
                        }
                    });
                }
                
                function addEmbedMessage(text, type) {
                    const messageClass = type.includes('user') ? 'user-message' : 'bot-message';
                    const errorClass = type.includes('error') ? ' error' : '';
                    const sender = type.includes('user') ? 'You' : 'Bot';
                    
                    const messageHtml = '<div class="' + messageClass + errorClass + '" style="margin-bottom: 15px; padding: 10px; border-radius: 8px; ' + 
                                      (type.includes('user') ? 'background: #0073aa; color: white; margin-left: 20%;' : 'background: #f5f5f5; margin-right: 20%;') + 
                                      '"><strong>' + sender + ':</strong> ' + escapeHtml(text) + '</div>';
                    messages.append(messageHtml);
                    messages.scrollTop(messages[0].scrollHeight);
                }
                
                function escapeHtml(text) {
                    const map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
                }
                
                sendBtn.on('click', sendEmbedMessage);
                input.on('keypress', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        sendEmbedMessage();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    // Enhanced admin menu with frontend settings
    public function hd_add_admin_menu() {
        add_menu_page(
            'Simple Chatbot Dashboard',
            'Simple Chatbot',
            'manage_options',
            'simple-chatbot-main',
            array($this, 'main_page'),
            'dashicons-format-quote',
            30
        );
        
        add_submenu_page(
            'simple-chatbot-main',
            'Settings',
            'Settings',
            'manage_options',
            'simple-chatbot-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'simple-chatbot-main',
            'Frontend Settings',
            'Frontend Settings',
            'manage_options',
            'simple-chatbot-frontend',
            array($this, 'frontend_settings_page')
        );
        
        add_submenu_page(
            'simple-chatbot-main',
            'Chat History',
            'Chat History',
            'manage_options',
            'simple-chatbot-history',
            array($this, 'history_page')
        );
    }
    
    // New frontend settings page
    public function frontend_settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'simple_chatbot_frontend_settings')) {
            update_option('simple_chatbot_enable_frontend', isset($_POST['enable_frontend']) ? 1 : 0);
            update_option('simple_chatbot_position', sanitize_text_field($_POST['position']));
            update_option('simple_chatbot_widget_title', sanitize_text_field($_POST['widget_title']));
            update_option('simple_chatbot_placeholder', sanitize_text_field($_POST['placeholder']));
            update_option('simple_chatbot_require_login', isset($_POST['require_login']) ? 1 : 0);
            echo '<div class="notice notice-success"><p>Frontend settings saved!</p></div>';
        }
        
        $enable_frontend = get_option('simple_chatbot_enable_frontend', 1);
        $position = get_option('simple_chatbot_position', 'bottom-right');
        $widget_title = get_option('simple_chatbot_widget_title', 'Chat with us!');
        $placeholder = get_option('simple_chatbot_placeholder', 'Type your message...');
        $require_login = get_option('simple_chatbot_require_login', 0);
        ?>
        <div class="wrap">
            <h1>Frontend Chatbot Settings</h1>
            <form method="post">
                <?php wp_nonce_field('simple_chatbot_frontend_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Frontend Chatbot</th>
                        <td>
                            <label><input type="checkbox" name="enable_frontend" value="1" <?php checked($enable_frontend, 1); ?> /> Enable chatbot on website frontend</label>
                            <p class="description">When enabled, visitors can use the chatbot on your website.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Widget Position</th>
                        <td>
                            <select name="position">
                                <option value="bottom-right" <?php selected($position, 'bottom-right'); ?>>Bottom Right</option>
                                <option value="bottom-left" <?php selected($position, 'bottom-left'); ?>>Bottom Left</option>
                                <option value="top-right" <?php selected($position, 'top-right'); ?>>Top Right</option>
                                <option value="top-left" <?php selected($position, 'top-left'); ?>>Top Left</option>
                            </select>
                            <p class="description">Choose where the chatbot widget appears on your website.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Widget Title</th>
                        <td>
                            <input type="text" name="widget_title" value="<?php echo esc_attr($widget_title); ?>" class="regular-text" />
                            <p class="description">Title shown in the chatbot header.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Input Placeholder</th>
                        <td>
                            <input type="text" name="placeholder" value="<?php echo esc_attr($placeholder); ?>" class="regular-text" />
                            <p class="description">Placeholder text in the message input field.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Require Login</th>
                        <td>
                            <label><input type="checkbox" name="require_login" value="1" <?php checked($require_login, 1); ?> /> Require users to be logged in to use chatbot</label>
                            <p class="description">If checked, only logged-in users can use the frontend chatbot.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h3>Shortcode Usage</h3>
                <p>You can also embed the chatbot directly in posts or pages using this shortcode:</p>
                <code>[simple_chatbot]</code>
                <p>With optional parameters:</p>
                <code>[simple_chatbot height="500px" width="100%" title="Customer Support"]</code>
            </div>
        </div>
        <?php
    }

    // Keep existing admin methods but add admin styles and scripts loading conditions
    public function add_admin_styles() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'simple-chatbot') === false) {
            return;
        }
        ?>
        <style>
        .chat-message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            line-height: 1.5;
            max-width: 90%;
            word-wrap: break-word;
            animation: fadeInUp 0.3s ease-out;
        }
        .user-message {
            background-color: #e3f2fd;
            margin-left: 20px;
            border-bottom-right-radius: 4px;
            border: 1px solid #bbdefb;
        }
        .bot-message {
            background-color: #f5f5f5;
            margin-right: 20px;
            border-bottom-left-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .bot-message.error {
            background-color: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }
        #chat-container {
            height: 400px;
            overflow-y: auto;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        #chat-container::-webkit-scrollbar {
            width: 8px;
        }
        #chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        #chat-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        #chat-input {
            border: 2px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        #chat-input:focus {
            border-color: #0073aa;
            outline: none;
            box-shadow: 0 0 0 1px #0073aa;
        }
        #send-button {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        #send-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        #send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        #loading {
            color: #666;
            font-style: italic;
            padding: 5px 0;
        }
        .chat-entry {
            background: #fff;
            border-left: 4px solid #0073aa;
            transition: box-shadow 0.3s ease;
        }
        .chat-entry:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .timestamp {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 768px) {
            .chat-message {
                max-width: 95%;
                padding: 10px;
                font-size: 14px;
            }
            #chat-container {
                height: 300px;
                padding: 10px;
            }
            #chat-input {
                font-size: 16px;
            }
        }
        </style>
        <?php
    }
    
    public function add_admin_scripts() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'simple-chatbot') === false) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Simple Chatbot: Script loaded');
            
            const chatMessages = $('#chat-messages');
            const chatInput = $('#chat-input');
            const sendButton = $('#send-button');
            const loading = $('#loading');
            const chatContainer = $('#chat-container');
            
            console.log('Elements found:', {
                chatMessages: chatMessages.length,
                chatInput: chatInput.length,
                sendButton: sendButton.length,
                loading: loading.length,
                chatContainer: chatContainer.length
            });
            
            // Send message function
            function sendMessage() {
                console.log('Send message triggered');
                const message = chatInput.val().trim();
                
                if (!message) {
                    console.log('Empty message, returning');
                    return;
                }
                
                console.log('Sending message:', message);
                
                // Add user message to chat
                addMessageToChat(message, 'user');
                
                // Clear input and disable controls
                chatInput.val('');
                chatInput.prop('disabled', true);
                sendButton.prop('disabled', true);
                loading.show();
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'send_chat_message',
                        message: message,
                        nonce: '<?php echo wp_create_nonce('simple_chatbot_nonce'); ?>',
                        is_admin: 1
                    },
                    success: function(response) {
                        console.log('AJAX Success:', response);
                        if (response.success) {
                            addMessageToChat(response.data, 'bot');
                        } else {
                            addMessageToChat('Error: ' + response.data, 'bot error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr.responseText, status, error);
                        addMessageToChat('Connection error: ' + error, 'bot error');
                    },
                    complete: function() {
                        console.log('AJAX Complete');
                        // Re-enable controls
                        chatInput.prop('disabled', false);
                        sendButton.prop('disabled', false);
                        loading.hide();
                        chatInput.focus();
                    }
                });
            }
            
            // Add message to chat display
            function addMessageToChat(message, type) {
                const messageClass = type === 'user' ? 'user-message' : 'bot-message';
                const sender = type === 'user' ? 'You' : 'Bot';
                const errorClass = type.includes('error') ? ' error' : '';
                
                const messageHtml = '<div class="chat-message ' + messageClass + errorClass + '"><strong>' + sender + ':</strong> ' + escapeHtml(message) + '</div>';
                
                chatMessages.append(messageHtml);
                
                // Scroll to bottom
                chatContainer.scrollTop(chatContainer[0].scrollHeight);
            }
            
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }
            
            // Event listeners
            sendButton.on('click', function(e) {
                e.preventDefault();
                console.log('Send button clicked');
                sendMessage();
            });
            
            chatInput.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) { // Enter key
                    e.preventDefault();
                    console.log('Enter key pressed');
                    sendMessage();
                }
            });
            
            // Focus on input when page loads
            chatInput.focus();
        });
        </script>
        <?php
    }
    
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'simple_chatbot_settings')) {
            update_option('simple_chatbot_hf_token', sanitize_text_field($_POST['hf_token']));
            update_option('simple_chatbot_model', sanitize_text_field($_POST['model']));
            update_option('simple_chatbot_system_message', sanitize_textarea_field($_POST['system_message']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $hf_token = get_option('simple_chatbot_hf_token', '');
        $model = get_option('simple_chatbot_model', 'deepseek-ai/DeepSeek-R1:novita');
        $system_message = get_option('simple_chatbot_system_message', 'You are a helpful assistant.');
        ?>
        <div class="wrap">
            <h1>Simple Chatbot Settings</h1>
            <form method="post">
                <?php wp_nonce_field('simple_chatbot_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Hugging Face Token</th>
                        <td>
                            <input type="password" name="hf_token" value="<?php echo esc_attr($hf_token); ?>" class="regular-text" />
                            <p class="description">Enter your Hugging Face API token. Get one from <a href="https://huggingface.co/settings/tokens" target="_blank">https://huggingface.co/settings/tokens</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <select name="model" class="regular-text">
                                <option value="deepseek-ai/DeepSeek-R1:novita" <?php selected($model, 'deepseek-ai/DeepSeek-R1:novita'); ?>>DeepSeek R1 (Novita)</option>
                                <option value="microsoft/DialoGPT-large" <?php selected($model, 'microsoft/DialoGPT-large'); ?>>DialoGPT Large</option>
                                <option value="microsoft/DialoGPT-medium" <?php selected($model, 'microsoft/DialoGPT-medium'); ?>>DialoGPT Medium</option>
                                <option value="facebook/blenderbot-400M-distill" <?php selected($model, 'facebook/blenderbot-400M-distill'); ?>>BlenderBot 400M</option>
                                <option value="custom" <?php selected($model, 'custom'); ?>>Custom Model</option>
                            </select>
                            <p class="description">Select the Hugging Face model to use for chat completions.</p>
                            <div id="custom-model" style="margin-top: 10px; <?php echo $model !== 'custom' ? 'display: none;' : ''; ?>">
                                <input type="text" name="custom_model" value="<?php echo esc_attr(get_option('simple_chatbot_custom_model', '')); ?>" class="regular-text" placeholder="Enter custom model name" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">System Message</th>
                        <td>
                            <textarea name="system_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea($system_message); ?></textarea>
                            <p class="description">This message sets the behavior of the AI assistant.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('select[name="model"]').change(function() {
                    if ($(this).val() === 'custom') {
                        $('#custom-model').show();
                    } else {
                        $('#custom-model').hide();
                    }
                });
            });
            </script>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h3>Debug Information</h3>
                <p><strong>HF Token Status:</strong> <?php echo !empty($hf_token) ? 'Configured (' . strlen($hf_token) . ' characters)' : 'Not configured'; ?></p>
                <p><strong>Selected Model:</strong> <?php echo esc_html($model); ?></p>
                <p><strong>WordPress AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
                <p><strong>Current User Can Manage Options:</strong> <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></p>
                <p><strong>Database Table Exists:</strong> 
                    <?php 
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'simple_chatbot_messages';
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                    echo $table_exists ? 'Yes' : 'No';
                    ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    public function history_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chatbot_messages';
        
        // Add filter for admin vs frontend messages
        $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
        $where_clause = '';
        if ($filter === 'admin') {
            $where_clause = 'WHERE is_admin = 1';
        } elseif ($filter === 'frontend') {
            $where_clause = 'WHERE is_admin = 0';
        }
        
        $messages = $wpdb->get_results("SELECT * FROM $table_name $where_clause ORDER BY timestamp DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1>Chat History</h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="filter" onchange="window.location.href='<?php echo admin_url('admin.php?page=simple-chatbot-history&filter='); ?>' + this.value">
                        <option value="all" <?php selected($filter, 'all'); ?>>All Messages</option>
                        <option value="admin" <?php selected($filter, 'admin'); ?>>Admin Messages</option>
                        <option value="frontend" <?php selected($filter, 'frontend'); ?>>Frontend Messages</option>
                    </select>
                </div>
            </div>
            
            <div class="chat-history">
                <?php if (empty($messages)): ?>
                    <p>No chat history found.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="chat-entry" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                            <div class="timestamp" style="font-size: 12px; color: #666; margin-bottom: 10px;">
                                <?php echo esc_html($msg->timestamp); ?> 
                                <span style="margin-left: 10px; padding: 2px 6px; background: <?php echo $msg->is_admin ? '#0073aa' : '#28a745'; ?>; color: white; border-radius: 3px; font-size: 10px;">
                                    <?php echo $msg->is_admin ? 'ADMIN' : 'FRONTEND'; ?>
                                </span>
                                <?php if (!$msg->is_admin && $msg->user_ip): ?>
                                    <span style="margin-left: 10px; color: #999; font-size: 10px;">
                                        IP: <?php echo esc_html($msg->user_ip); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="user-message" style="margin-bottom: 10px;">
                                <strong>User:</strong> <?php echo esc_html($msg->message); ?>
                            </div>
                            <div class="bot-response">
                                <strong>Bot:</strong> <?php echo esc_html($msg->response); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function main_page() {
        $hf_token = get_option('simple_chatbot_hf_token', '');
        ?>
        <div class="wrap">
            <h1>Simple Chatbot Dashboard</h1>
            
            <?php if (empty($hf_token)): ?>
                <div class="notice notice-warning">
                    <p>Please configure your Hugging Face token in <a href="<?php echo admin_url('admin.php?page=simple-chatbot-settings'); ?>">Settings</a> to use the chatbot.</p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px;">
                <h2>Hugging Face AI Chat</h2>
                <div id="chat-container">
                    <div id="chat-messages">
                        <div class="chat-message bot-message">
                            <strong>Bot:</strong> Hello! I'm your AI assistant powered by Hugging Face. How can I help you today?
                        </div>
                    </div>
                </div>
                
                <div id="chat-input-container">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="chat-input" placeholder="Type your message here..." style="flex: 1; padding: 8px;" <?php echo empty($hf_token) ? 'disabled' : ''; ?> />
                        <button id="send-button" class="button button-primary" <?php echo empty($hf_token) ? 'disabled' : ''; ?>>Send</button>
                    </div>
                    <div id="loading" style="display: none; margin-top: 10px;">
                        <em>Bot is thinking...</em>
                    </div>
                </div>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3>Quick Actions</h3>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=simple-chatbot-settings'); ?>" class="button button-primary">
                        Plugin Settings
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=simple-chatbot-frontend'); ?>" class="button button-secondary" style="margin-left: 10px;">
                        Frontend Settings
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=simple-chatbot-history'); ?>" class="button button-secondary" style="margin-left: 10px;">
                        View Chat History
                    </a>
                </p>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3>Frontend Status</h3>
                <p><strong>Frontend Chatbot:</strong> 
                    <span style="color: <?php echo get_option('simple_chatbot_enable_frontend', 1) ? 'green' : 'red'; ?>;">
                        <?php echo get_option('simple_chatbot_enable_frontend', 1) ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </p>
                <p><strong>Widget Position:</strong> <?php echo esc_html(ucwords(str_replace('-', ' ', get_option('simple_chatbot_position', 'bottom-right')))); ?></p>
                <p><strong>Shortcode:</strong> <code>[simple_chatbot]</code></p>
            </div>
        </div>
        <?php
    }
    
    public function handle_chat_message() {
        // Add debug logging
        error_log('Simple Chatbot: handle_chat_message called');
        error_log('POST data: ' . print_r($_POST, true));
        
        $is_frontend = isset($_POST['is_frontend']) && $_POST['is_frontend'];
        $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'];
        
        // Check permissions based on context
        if ($is_admin && !current_user_can('manage_options')) {
            error_log('Simple Chatbot: Admin user does not have permissions');
            wp_send_json_error('Permission denied');
            return;
        }
        
        if ($is_frontend) {
            // Check if login is required for frontend
            $require_login = get_option('simple_chatbot_require_login', 0);
            if ($require_login && !is_user_logged_in()) {
                error_log('Simple Chatbot: Frontend user not logged in');
                wp_send_json_error('Please log in to use the chatbot');
                return;
            }
            
            // Check if frontend is enabled
            if (!get_option('simple_chatbot_enable_frontend', 1)) {
                error_log('Simple Chatbot: Frontend chatbot disabled');
                wp_send_json_error('Chatbot is currently unavailable');
                return;
            }
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simple_chatbot_nonce')) {
            error_log('Simple Chatbot: Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!isset($_POST['message'])) {
            error_log('Simple Chatbot: No message in POST data');
            wp_send_json_error('No message provided');
            return;
        }
        
        $message = sanitize_text_field($_POST['message']);
        $hf_token = get_option('simple_chatbot_hf_token', '');
        $model = get_option('simple_chatbot_model', 'deepseek-ai/DeepSeek-R1:novita');
        $system_message = get_option('simple_chatbot_system_message', 'You are a helpful assistant.');
        
        // Handle custom model
        if ($model === 'custom') {
            $model = get_option('simple_chatbot_custom_model', 'deepseek-ai/DeepSeek-R1:novita');
        }
        
        error_log('Simple Chatbot: Message: ' . $message);
        error_log('Simple Chatbot: HF Token length: ' . strlen($hf_token));
        error_log('Simple Chatbot: Model: ' . $model);
        error_log('Simple Chatbot: Is Frontend: ' . ($is_frontend ? 'Yes' : 'No'));
        
        if (empty($hf_token)) {
            error_log('Simple Chatbot: HF token not configured');
            wp_send_json_error('Chatbot service is not configured. Please contact the administrator.');
            return;
        }
        
        // Make API call to Hugging Face
        $response = $this->call_huggingface_api($message, $hf_token, $model, $system_message);
        
        if (is_wp_error($response)) {
            error_log('Simple Chatbot: API Error: ' . $response->get_error_message());
            wp_send_json_error('Sorry, I encountered an error. Please try again.');
            return;
        }
        
        error_log('Simple Chatbot: API Response: ' . $response);
        
        // Save to database with additional context
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chatbot_messages';
        $result = $wpdb->insert(
            $table_name,
            array(
                'message' => $message,
                'response' => $response,
                'user_ip' => $is_frontend ? $this->get_client_ip() : null,
                'user_agent' => $is_frontend ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
                'is_admin' => $is_admin ? 1 : 0
            )
        );
        
        if ($result === false) {
            error_log('Simple Chatbot: Database insert failed: ' . $wpdb->last_error);
        }
        
        wp_send_json_success($response);
    }
    
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function call_huggingface_api($message, $hf_token, $model, $system_message) {
        error_log('Simple Chatbot: Making API call to Hugging Face');
        
        $url = 'https://router.huggingface.co/v1/chat/completions';
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_message
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => 1000,
            'temperature' => 0.7
        );
        
        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $hf_token
            ),
            'timeout' => 300,
            'method' => 'POST'
        );
        
        error_log('Simple Chatbot: API Request body: ' . json_encode($body));
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Simple Chatbot: wp_remote_post error: ' . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('Simple Chatbot: API Response code: ' . $response_code);
        error_log('Simple Chatbot: API Response body: ' . $body);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API returned status code: ' . $response_code . ' - ' . $body);
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('api_error', 'Invalid JSON response from API');
        }
        
        if (isset($data['error'])) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            return new WP_Error('api_error', $error_message);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Invalid response structure from API');
        }
        
        return $data['choices'][0]['message']['content'];
    }
}

new SimpleChatbot();
?>
```




## this whole file system looks like this 
```
simple-chatbot
       |__ includes
       |       |__ class-simple-chatbot-admin-pages.php
       |       |__ class-simple-chatbot-ajax-handler.php
       |       |__ class-simple-chatbot-api-client.php
       |       |__ class-simple-chatbot-frontend.php
       |       |__ class-simple-chatbot-loader.php
       |
       |__ simple-chatbot.php 

```
## class-simple-chatbot-admin-pages.php
```php
<?php
class Simple_Chatbot_Admin_Pages {

    private $database_handler;

    public function __construct($database_handler) {
        $this->database_handler = $database_handler;
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_head', array($this, 'add_admin_styles'));
        add_action('admin_footer', array($this, 'add_admin_scripts'));
    }

    public function add_admin_menu() {
        // Add top-level menu page
        add_menu_page(
            'Simple Chatbot Dashboard',
            'Simple Chatbot',
            'manage_options',
            'simple-chatbot-main',
            array($this, 'main_page'),
            'dashicons-format-quote',
            30
        );
        // Add submenu pages
        add_submenu_page('simple-chatbot-main', 'Settings', 'Settings', 'manage_options', 'simple-chatbot-settings', array($this, 'settings_page'));
        add_submenu_page('simple-chatbot-main', 'Chat History', 'Chat History', 'manage_options', 'simple-chatbot-history', array($this, 'history_page'));
    }

    // Include the actual HTML/PHP for each page here (or in separate template files)
    public function main_page() {
        // get the value corresponding to simple_chatbot_hf_token
        $hf_token = get_option('simple_chatbot_hf_token', '');
        ?>
        <div class="wrap">
            <h1>Simple Chatbot Dashboard</h1>
            
            <!-- check if there is any hf token -->
            <?php if (empty($hf_token)): ?>
                <div class="notice notice-warning">
                    <p>Please configure your Hugging Face token in <a href="<?php echo admin_url('admin.php?page=simple-chatbot-settings'); ?>">Settings</a> to use the chatbot.</p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px;">
                <h2>Hugging Face AI Chat</h2>
                <div id="chat-container">
                    <div id="chat-messages">
                        <div class="chat-message bot-message">
                            <strong>Bot:</strong> Hello! I'm your AI assistant powered by Hugging Face. How can I help you today?
                        </div>
                    </div>
                </div>
                
                <div id="chat-input-container">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="chat-input" placeholder="Type your message here..." style="flex: 1; padding: 8px;" <?php echo empty($hf_token) ? 'disabled' : ''; ?> />
                        <button id="send-button" class="button button-primary" <?php echo empty($hf_token) ? 'disabled' : ''; ?>>Send</button>
                    </div>
                    <div id="loading" style="display: none; margin-top: 10px;">
                        <em>Bot is thinking...</em>
                    </div>
                </div>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3>Quick Actions</h3>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=simple-chatbot-settings'); ?>" class="button button-primary">
                        Plugin Settings
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=simple-chatbot-history'); ?>" class="button button-secondary" style="margin-left: 10px;">
                        View Chat History
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'simple_chatbot_settings')) {
            update_option('simple_chatbot_hf_token', sanitize_text_field($_POST['hf_token']));
            update_option('simple_chatbot_model', sanitize_text_field($_POST['model']));
            update_option('simple_chatbot_system_message', sanitize_textarea_field($_POST['system_message']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $hf_token = get_option('simple_chatbot_hf_token', '');
        $model = get_option('simple_chatbot_model', 'deepseek-ai/DeepSeek-R1:novita');
        $system_message = get_option('simple_chatbot_system_message', 'You are a helpful assistant.');
        ?>
        <div class="wrap">
            <h1>Simple Chatbot Settings</h1>
            <form method="post">
                <?php wp_nonce_field('simple_chatbot_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Hugging Face Token</th>
                        <td>
                            <input type="password" name="hf_token" value="<?php echo esc_attr($hf_token); ?>" class="regular-text" />
                            <p class="description">Enter your Hugging Face API token. Get one from <a href="https://huggingface.co/settings/tokens" target="_blank">https://huggingface.co/settings/tokens</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Model</th>
                        <td>
                            <select name="model" class="regular-text">
                                <option value="deepseek-ai/DeepSeek-R1:novita" <?php selected($model, 'deepseek-ai/DeepSeek-R1:novita'); ?>>DeepSeek R1 (Novita)</option>
                                <option value="microsoft/DialoGPT-large" <?php selected($model, 'microsoft/DialoGPT-large'); ?>>DialoGPT Large</option>
                                <option value="microsoft/DialoGPT-medium" <?php selected($model, 'microsoft/DialoGPT-medium'); ?>>DialoGPT Medium</option>
                                <option value="facebook/blenderbot-400M-distill" <?php selected($model, 'facebook/blenderbot-400M-distill'); ?>>BlenderBot 400M</option>
                                <option value="custom" <?php selected($model, 'custom'); ?>>Custom Model</option>
                            </select>
                            <p class="description">Select the Hugging Face model to use for chat completions.</p>
                            <div id="custom-model" style="margin-top: 10px; <?php echo $model !== 'custom' ? 'display: none;' : ''; ?>">
                                <input type="text" name="custom_model" value="<?php echo esc_attr(get_option('simple_chatbot_custom_model', '')); ?>" class="regular-text" placeholder="Enter custom model name" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">System Message</th>
                        <td>
                            <textarea name="system_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea($system_message); ?></textarea>
                            <p class="description">This message sets the behavior of the AI assistant.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('select[name="model"]').change(function() {
                    if ($(this).val() === 'custom') {
                        $('#custom-model').show();
                    } else {
                        $('#custom-model').hide();
                    }
                });
            });
            </script>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h3>Debug Information</h3>
                <p><strong>HF Token Status:</strong> <?php echo !empty($hf_token) ? 'Configured (' . strlen($hf_token) . ' characters)' : 'Not configured'; ?></p>
                <p><strong>Selected Model:</strong> <?php echo esc_html($model); ?></p>
                <p><strong>WordPress AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></p>
                <p><strong>Current User Can Manage Options:</strong> <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></p>
                <p><strong>Database Table Exists:</strong> 
                    <?php 
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'simple_chatbot_messages';
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                    echo $table_exists ? 'Yes' : 'No';
                    ?>
                </p>
            </div>
        </div>
        <?php
    }
    public function history_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chatbot_messages';
        
        $messages = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 50");
        ?>
        <div class="wrap">
            <h1>Chat History</h1>
            <div class="chat-history">
                <?php if (empty($messages)): ?>
                    <p>No chat history found.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="chat-entry" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                            <div class="timestamp" style="font-size: 12px; color: #666; margin-bottom: 10px;">
                                <?php echo esc_html($msg->timestamp); ?>
                            </div>
                            <div class="user-message" style="margin-bottom: 10px;">
                                <strong>User:</strong> <?php echo esc_html($msg->message); ?>
                            </div>
                            <div class="bot-response">
                                <strong>Bot:</strong> <?php echo esc_html($msg->response); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    public function add_admin_styles() {
        // Only load on our plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'simple-chatbot') === false) {
            return;
        }
        ?>
        <style>
        .chat-message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            line-height: 1.5;
            max-width: 90%;
            word-wrap: break-word;
            animation: fadeInUp 0.3s ease-out;
        }
        .user-message {
            background-color: #e3f2fd;
            margin-left: 20px;
            border-bottom-right-radius: 4px;
            border: 1px solid #bbdefb;
        }
        .bot-message {
            background-color: #f5f5f5;
            margin-right: 20px;
            border-bottom-left-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .bot-message.error {
            background-color: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }
        #chat-container {
            height: 400px;
            overflow-y: auto;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        #chat-container::-webkit-scrollbar {
            width: 8px;
        }
        #chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        #chat-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        #chat-input {
            border: 2px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        #chat-input:focus {
            border-color: #0073aa;
            outline: none;
            box-shadow: 0 0 0 1px #0073aa;
        }
        #send-button {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        #send-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        #send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        #loading {
            color: #666;
            font-style: italic;
            padding: 5px 0;
        }
        .chat-entry {
            background: #fff;
            border-left: 4px solid #0073aa;
            transition: box-shadow 0.3s ease;
        }
        .chat-entry:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .timestamp {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 768px) {
            .chat-message {
                max-width: 95%;
                padding: 10px;
                font-size: 14px;
            }
            #chat-container {
                height: 300px;
                padding: 10px;
            }
            #chat-input {
                font-size: 16px;
            }
        }
        </style>
        <?php
    }
    public function add_admin_scripts() {
        // Only load on our plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'simple-chatbot') === false) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Simple Chatbot: Script loaded');
            
            const chatMessages = $('#chat-messages');
            const chatInput = $('#chat-input');
            const sendButton = $('#send-button');
            const loading = $('#loading');
            const chatContainer = $('#chat-container');
            
            console.log('Elements found:', {
                chatMessages: chatMessages.length,
                chatInput: chatInput.length,
                sendButton: sendButton.length,
                loading: loading.length,
                chatContainer: chatContainer.length
            });
            
            // Send message function
            function sendMessage() {
                console.log('Send message triggered');
                const message = chatInput.val().trim();
                
                if (!message) {
                    console.log('Empty message, returning');
                    return;
                }
                
                console.log('Sending message:', message);
                
                // Add user message to chat
                addMessageToChat(message, 'user');
                
                // Clear input and disable controls
                chatInput.val('');
                chatInput.prop('disabled', true);
                sendButton.prop('disabled', true);
                loading.show();
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'send_chat_message',
                        message: message,
                        nonce: '<?php echo wp_create_nonce('simple_chatbot_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('AJAX Success:', response);
                        if (response.success) {
                            addMessageToChat(response.data, 'bot');
                        } else {
                            addMessageToChat('Error: ' + response.data, 'bot error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr.responseText, status, error);
                        addMessageToChat('Connection error: ' + error, 'bot error');
                    },
                    complete: function() {
                        console.log('AJAX Complete');
                        // Re-enable controls
                        chatInput.prop('disabled', false);
                        sendButton.prop('disabled', false);
                        loading.hide();
                        chatInput.focus();
                    }
                });
            }
            
            // Add message to chat display
            function addMessageToChat(message, type) {
                const messageClass = type === 'user' ? 'user-message' : 'bot-message';
                const sender = type === 'user' ? 'You' : 'Bot';
                const errorClass = type.includes('error') ? ' error' : '';
                
                const messageHtml = '<div class="chat-message ' + messageClass + errorClass + '"><strong>' + sender + ':</strong> ' + escapeHtml(message) + '</div>';
                
                chatMessages.append(messageHtml);
                
                // Scroll to bottom
                chatContainer.scrollTop(chatContainer[0].scrollHeight);
            }
            
            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }
            
            // Event listeners
            sendButton.on('click', function(e) {
                e.preventDefault();
                console.log('Send button clicked');
                sendMessage();
            });
            
            chatInput.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) { // Enter key
                    e.preventDefault();
                    console.log('Enter key pressed');
                    sendMessage();
                }
            });
            
            // Focus on input when page loads
            chatInput.focus();
        });
        </script>
        <?php
    }
}
```
## class-simple-chatbot-ajax-handler.php
```php
<?php
class Simple_Chatbot_Ajax_Handler {
    // database_handler is being instantiated elsewhere.
    // Then passed into the ajax handler.
    private $database_handler;

    public function __construct($database_handler) {
        $this->database_handler = $database_handler;
        
        add_action('wp_ajax_send_chat_message', array($this, 'handle_chat_message'));
    }

    public function handle_chat_message() {
        // ... (logic for checking permissions, nonce, sanitizing input) ...
        $message = sanitize_text_field($_POST['message']);

        // Get settings
        $hf_token = get_option('simple_chatbot_hf_token', '');
        $model = get_option('simple_chatbot_model', 'deepseek-ai/DeepSeek-R1:novita');
        // ... handle custom model ...
        $system_message = get_option('simple_chatbot_system_message', 'You are a helpful assistant.');

        if (empty($hf_token)) {
             wp_send_json_error('Hugging Face token not configured');
             return;
        }

        // Use API Client
        $api_client = new Simple_Chatbot_Api_Client();
        $response = $api_client->call_huggingface_api($message, $hf_token, $model, $system_message);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        // Save to DB
        $save_result = $this->database_handler->save_message($message, $response);
        if ($save_result === false) {
            error_log('Simple Chatbot: Database insert failed: ' . $wpdb->last_error); // Or use $this->database_handler's internal error handling
        }

        wp_send_json_success($response);
    }
}
```
## class-simple-chatbot-api-client.php
```php

<?php

class Simple_Chatbot_Api_Client {
    public function call_huggingface_api($message, $hf_token, $model, $system_message) {
        error_log('Simple Chatbot: Making API call to Hugging Face');
        
        $url = 'https://router.huggingface.co/v1/chat/completions';
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $system_message
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => 1000,
            'temperature' => 0.7
        );
        
        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $hf_token
            ),
            'timeout' => 300,
            'method' => 'POST'
        );
        
        error_log('Simple Chatbot: API Request body: ' . json_encode($body));
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Simple Chatbot: wp_remote_post error: ' . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('Simple Chatbot: API Response code: ' . $response_code);
        error_log('Simple Chatbot: API Response body: ' . $body);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API returned status code: ' . $response_code . ' - ' . $body);
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('api_error', 'Invalid JSON response from API');
        }
        
        if (isset($data['error'])) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            return new WP_Error('api_error', $error_message);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Invalid response structure from API');
        }
        
        return $data['choices'][0]['message']['content'];
    }
}
```
