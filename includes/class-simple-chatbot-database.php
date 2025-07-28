<?php
class Simple_Chatbot_Database {

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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function save_message($message, $response) {
         global $wpdb;
         $table_name = $wpdb->prefix . 'simple_chatbot_messages';
         return $wpdb->insert(
             $table_name,
             array(
                 'message' => $message,
                 'response' => $response
             )
         );
    }

    public function get_history($limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chatbot_messages';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d", $limit));
    }
}