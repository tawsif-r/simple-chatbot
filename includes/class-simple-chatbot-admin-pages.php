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