<?php
class Simple_Chatbot_Frontend {
    private $database_handler;

    public function __construct($database_handler) {
        $this->database_handler = $database_handler;
        add_shortcode('simple_chatbot', array($this, 'render_chatbot'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function render_chatbot() {
        $hf_token = get_option('simple_chatbot_hf_token', '');
        ob_start();
        ?>
        <div id="simple-chatbot-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <button id="chatbot-toggle" style="background: #0073aa; border: none; border-radius: 50%; width: 60px; height: 60px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center;">
                <span style="color: white; font-size: 24px;">💬</span>
            </button>
            <div id="chatbot-window" style="display: none; width: 350px; height: 500px; background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.2); margin-top: 10px; overflow: hidden;">
                <div id="chatbot-header" style="background: #0073aa; color: white; padding: 10px; font-size: 16px; font-weight: bold; text-align: center;">
                    AI Assistant
                </div>
                <div id="chat-messages" style="height: 400px; overflow-y: auto; padding: 15px; background: #fafafa;">
                    <div class="chat-message bot-message">
                        <strong>Bot:</strong> Hello! I'm your AI assistant. How can I help you today?
                    </div>
                </div>
                <div id="chat-input-container" style="padding: 10px; border-top: 1px solid #ddd;">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="chat-input" placeholder="Type your message..." style="flex: 1; padding: 8px; border: 2px solid #ddd; border-radius: 6px;" <?php echo empty($hf_token) ? 'disabled' : ''; ?> />
                        <button id="send-button" style="padding: 8px 16px; background: #0073aa; color: white; border: none; border-radius: 6px;" <?php echo empty($hf_token) ? 'disabled' : ''; ?>>Send</button>
                    </div>
                    <div id="loading" style="display: none; margin-top: 10px; color: #666; font-style: italic;">
                        <em>Bot is thinking...</em>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('simple-chatbot-frontend', plugins_url('css/simple-chatbot-frontend.css', __FILE__), array(), '1.0');
        wp_enqueue_script('simple-chatbot-frontend', plugins_url('js/simple-chatbot-frontend.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('simple-chatbot-frontend', 'simpleChatbot', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simple_chatbot_nonce')
        ));
    }
}