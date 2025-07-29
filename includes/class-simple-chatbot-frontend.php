<?php

class Simple_Chatbot_Frontend {
    private $database_handler;

    public function __construct($database_handler){
        $this->database_handler = $database_handler;
        add_action('wp_footer', array($this,'chatwidget'));
        add_action('wp_head', array($this, 'add_frontend_styles'));
        add_shortcode('simple_chatbot',array($this,'chatwidget_shortcode'));
    }

    public function chatwidget(){
        ?>
        <div id="simple-chatbot-widget">
            <div id="chat-toggle-btn">
                <span>💬</span>
            </div>
            <div id="chat-popup" class="chat-hidden">
                <div id="chat-header">
                    <h3>AI Chat Assistant</h3>
                    <button id="chat-close-btn">&times;</button>
                </div>
                <div id="chat-messages">
                    <div class="chat-message bot-message">
                        <div class="message-content">Hello! I'm your AI assistant. How can I help you today?</div>
                        <div class="message-time"><?php echo date('H:i'); ?></div>
                    </div>
                </div>
                <div id="chat-input-area">
                    <div id="loading" class="chat-hidden">
                        <span>🤖 Thinking...</span>
                    </div>
                    <div class="input-container">
                        <input type="text" id="chat-input" placeholder="Type your message..." />
                        <button id="send-button">Send</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function chatwidget_shortcode(){
        ob_start();
        $this->chatwidget();
        return ob_get_clean();
    }

    public function enqueue_frontend_scripts(){
        

        //localize script to pass ajax url and nonce
    }
    
    public function add_frontend_styles() {
        ?>
        <style>
        /* Chat Widget Container */
        #simple-chatbot-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Toggle Button */
        #chat-toggle-btn {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            color: white;
            font-size: 24px;
        }

        #chat-toggle-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }

        /* Chat Popup */
        #chat-popup {
            position: absolute;
            bottom: 70px;
            right: 0;
            width: 350px;
            height: 450px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Chat Header */
        #chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #chat-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        #chat-close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Chat Messages */
        #chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
        }

        #chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        #chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #chat-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .chat-message {
            margin-bottom: 15px;
            max-width: 85%;
            animation: fadeInUp 0.3s ease-out;
        }

        .user-message {
            margin-left: auto;
        }

        .bot-message {
            margin-right: auto;
        }

        .message-content {
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.4;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .user-message .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .bot-message .message-content {
            background: white;
            border: 1px solid #e9ecef;
            color: #333;
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
            text-align: right;
            padding: 0 8px;
        }

        /* Input Area */
        #chat-input-area {
            padding: 15px;
            background: white;
            border-top: 1px solid #e9ecef;
        }

        #loading {
            text-align: center;
            padding: 5px 0;
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .input-container {
            display: flex;
            gap: 10px;
        }

        #chat-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        #chat-input:focus {
            border-color: #667eea;
        }

        #send-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        #send-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        #send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Hidden State */
        .chat-hidden {
            display: none;
        }

        /* Animations */
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

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #chat-popup {
            animation: slideInUp 0.3s ease-out;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            #chat-popup {
                width: 300px;
                height: 400px;
                bottom: 70px;
                right: 10px;
            }

            #chat-toggle-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .chat-message {
                max-width: 90%;
            }

            .message-content {
                padding: 10px 14px;
                font-size: 13px;
            }

            #chat-input {
                padding: 10px 12px;
                font-size: 13px;
            }

            #send-button {
                padding: 10px 16px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            #chat-popup {
                width: 280px;
                height: 350px;
                right: 5px;
            }

            #simple-chatbot-widget {
                bottom: 15px;
                right: 15px;
            }
        }
        </style>
        <?php
    }
}
?>