<?php
class Simple_Chatbot_Ajax_Handler {
    // database_handler is being instantiated elsewhere.
    // Then passed into the ajax handler.
    private $database_handler;

    public function __construct($database_handler) {
        $this->database_handler = $database_handler;
        
        add_action('wp_ajax_send_chat_message', array($this, 'handle_chat_message'));
        // Handle AJAX for frontend users (new)
        // ========= actions ============
        // send_chat_message is the custom action which is defined in the javascript file
        add_action('wp_ajax_nopriv_send_chat_message', array($this, 'handle_chat_message'));
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

        #======================================
        # API CALL TO HUGGING FACE
        #======================================
        $response = $api_client->call_huggingface_api($message, $hf_token, $model, $system_message);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        // Save to DB
        $save_result = $this->database_handler->save_message($message, $response); # saving the response in the database
        if ($save_result === false) {
            error_log('Simple Chatbot: Database insert failed: ' . $wpdb->last_error); // Or use $this->database_handler's internal error handling
        }

        wp_send_json_success($response);
    }
}