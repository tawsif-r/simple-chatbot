jQuery(document).ready(function($) {
    console.log('Simple Chatbot Frontend: Script loaded');

    const chatbotContainer = $('#simple-chatbot-container');
    const chatbotToggle = $('#chatbot-toggle');
    const chatbotWindow = $('#chatbot-window');
    const chatMessages = $('#chat-messages');
    const chatInput = $('#chat-input');
    const sendButton = $('#send-button');
    const loading = $('#loading');

    // Toggle chatbot window
    chatbotToggle.on('click', function() {
        chatbotWindow.toggle();
        if (chatbotWindow.is(':visible')) {
            chatInput.focus();
            chatMessages.scrollTop(chatMessages[0].scrollHeight);
        }
    });

    // Send message function
    function sendMessage() {
        console.log('Frontend: Send message triggered');
        const message = chatInput.val().trim();

        if (!message) {
            console.log('Frontend: Empty message, returning');
            return;
        }

        console.log('Frontend: Sending message:', message);

        // Add user message to chat
        addMessageToChat(message, 'user');

        // Clear input and disable controls
        chatInput.val('');
        chatInput.prop('disabled', true);
        sendButton.prop('disabled', true);
        loading.show();

        // Send AJAX request
        // action defination here
        // send_chat_message is the name of the action which is called in the ajax handler.
        $.ajax({
            url: simpleChatbot.ajaxurl,
            type: 'POST',
            data: {
                action: 'send_chat_message',
                message: message,
                nonce: simpleChatbot.nonce
            },
            success: function(response) {
                console.log('Frontend: AJAX Success:', response);
                if (response.success) {
                    addMessageToChat(response.data, 'bot');
                } else {
                    addMessageToChat('Error: ' + response.data, 'bot error');
                }
            },
            error: function(xhr, status, error) {
                console.log('Frontend: AJAX Error:', xhr.responseText, status, error);
                addMessageToChat('Connection error: ' + error, 'bot error');
            },
            complete: function() {
                console.log('Frontend: AJAX Complete');
                chatInput.prop('disabled', false);
                sendButton.prop('disabled', false);
                loading.hide();
                chatInput.focus();
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
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
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
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
        console.log('Frontend: Send button clicked');
        sendMessage();
    });

    chatInput.on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            console.log('Frontend: Enter key pressed');
            sendMessage();
        }
    });

    // Focus on input when window is opened
    chatInput.focus();
});