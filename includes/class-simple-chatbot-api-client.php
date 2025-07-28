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