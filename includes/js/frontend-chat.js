jQuery(document).ready(function($) {
    // Make sure we're on a page with the chat widget
    if ($('#simple-chatbot-widget').length === 0) {
        return;
    }

    console.log('Simple Chatbot Frontend: Script loaded');
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
            url: simple_chatbot_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'send_chat_message',
                message: message
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
            '<': '<',
            '>': '>',
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