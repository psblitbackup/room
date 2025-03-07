@extends('layouts.app')

@section('content')
<div id="chat-widget" class="chat-widget">
    <!-- Chat Widget Button -->
    <div class="chat-widget-button" onclick="toggleChatBox()">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" class="fs-4">
            <rect width="40" height="40" rx="20" fill="" />
            <path d="M20 10C14.477 10 10 13.805 10 18.5C10 20.647 10.969 22.648 12.641 24.162C12.339 25.223 11.732 27.238 11.343 28.387C11.251 28.657 11.63 28.893 11.861 28.709C13.171 27.64 15.394 26.035 16.472 25.383C17.62 25.788 18.779 26 20 26C25.523 26 30 22.195 30 17.5C30 12.805 25.523 10 20 10Z" fill="#FFFFFF" />
        </svg>
    </div>
    
    <!-- Chat Widget Box -->
    <div class="chat-widget-box">
        <div class="chat-widget-header">
            <h4>Chat Support</h4>
            <button class="close-btn" onclick="toggleChatBox()">&times;</button>
        </div>
        <div class="agent-details text-center">
            <span><strong id="chat-status"></strong></span>
        </div>

        <!-- Waiting Loader -->
        <div id="waiting-loader" style="display: none; text-align: center;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Waiting for an agent...</p>
        </div>

        <!-- Start Chat Form -->
        <div id="start-chat-form" class="chat-widget-form">
            <form>
                <div class="form-group mb-3">
                    <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                </div>
                <div class="form-group mb-3">
                    <input type="tel" name="contact" class="form-control" placeholder="Your Phone" required>
                </div>
                <div class="form-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Your Email (optional)">
                </div>
                <button type="submit" id="start-chat" class="btn btn-primary w-100">Start Chat</button>
            </form>
        </div>

        <!-- Chat Interface (Hidden Initially) -->
        <div id="chat-interface" class="chat-widget-messages" style="display: none;">
            <div id="chat-messages" class="message-content"></div>
            <form id="message-form" class="message-form">
                <div class="input-group">
                    <input type="text" name="message" id="message-input" class="form-control" placeholder="Type your message..." required>
                    <button type="button" id="send-message" class="btn btn-primary">Send</button>
                    <button type="button" id="leave-chat" class="btn btn-danger">Leave</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .chat-widget-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .chat-widget-box {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
        }

        .chat-widget-header {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-widget-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .chat-widget-form {
            padding: 15px;
            border-top: 1px solid #dee2e6;
        }

        .message-content {
            height: 350px;
            overflow-y: auto;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 10px;
        }

        .message-form {
            display: flex;
            gap: 10px;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }
        <style>
    /* Chat messages container */
    .message-content {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 10px;
        overflow-y: auto;
        height: 350px;
        border-bottom: 1px solid #dee2e6;
    }

    /* Base message styling */
    .message {
        max-width: 80%;
        padding: 5px;
        border-radius: 10px;
        position: relative;
        margin-bottom: 5px;
    }

    /* Visitor messages (right side) */
    .visitor-message {
        align-self: flex-end;
        background-color: #0d6efd; /* Blue color for visitor messages */
        color: white;
        margin-left: auto;
    }

    /* Agent messages (left side) */
    .agent-message {
        align-self: flex-start;
        background-color: #f1f1f1; /* Light gray color for agent messages */
        color: black;
        margin-right: auto;
    }

    /* Message text styling */
    .message-text {
        word-wrap: break-word;
        font-size: 14px; /* Adjust the font size as needed */
    }
/* Timestamps */
.message-time {
    font-size: 9px !important; /* Smaller font size for timestamps */
    color: #cecaca;
    margin-top: 5px;
}

/* Avatars */
.message-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-size: 14px; /* Adjust font size for avatar text/emoji */
}
</style>
    </style>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pusher = new Pusher("73fd4859f7ea3b680067", {
        cluster: "ap1",
        encrypted: true
    });

    const chatId = '{{ session("chat_id") }}';
    if (chatId) {
        // Restore the chat interface
        document.getElementById('start-chat-form').style.display = 'none';
        document.getElementById('chat-interface').style.display = 'block';

        // Fetch chat history
        fetch(`/chat/history?chat_id=${chatId}`)
            .then(response => response.json())
            .then(data => {
                const chatMessages = document.getElementById('chat-messages');
                data.messages.forEach(message => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = message.sender === 'You' ? 'message visitor-message' : 'message agent-message';
                    messageDiv.innerHTML = `
                        <div class="message-text">${message.sender}: ${message.message}</div>
                        <div class="message-time">${new Date().toLocaleTimeString()}</div>
                    `;
                    chatMessages.appendChild(messageDiv);
                });

                // Scroll to the bottom of the chat messages
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });

        // Reinitialize Pusher subscription
        const channel = pusher.subscribe('chat.' + chatId);

        // Listen for incoming messages
        channel.bind('message.sent', function (data) {
            const chatMessages = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = data.sender === 'You' ? 'message visitor-message' : 'message agent-message';
            messageDiv.innerHTML = `
                <div class="message-text">${data.sender}: ${data.message}</div>
                <div class="message-time">${new Date().toLocaleTimeString()}</div>
            `;
            chatMessages.appendChild(messageDiv);
            // Scroll to the bottom of the chat messages
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });

        // Listen for chat ended event
        channel.bind('chat.ended', function(data) {
            console.log('Chat ended event received:', data); // Debugging

            // Disable message input and send button
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-message');
            if (messageInput && sendButton) {
                messageInput.disabled = true;
                sendButton.disabled = true;
            }

            // Display a notification
            const chatMessages = document.getElementById('chat-messages');
            const notification = document.createElement('div');
            notification.className = 'text-center text-muted mt-3';
            notification.textContent = data.message; // "The agent has ended the chat session."
            chatMessages.appendChild(notification);

            // Scroll to the bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;

            // Clear the chat_id session on the backend
            fetch('/visitor/clear-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('Session cleared successfully.');
                }
            })
            .catch(error => console.error('Error clearing session:', error));
        });

        // Send message functionality
        document.getElementById('send-message').addEventListener('click', function () {
            const message = document.getElementById('message-input').value;
            if (message.trim() === '') return;

            fetch('/visitor/send-message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ chat_id: chatId, message }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('message-input').value = '';
                }
            });
        });

        // Send message on pressing 'Enter'
        document.getElementById('message-input').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('send-message').click();
            }
        });
    }

    // Start chat form submission
    document.getElementById('start-chat-form').addEventListener('submit', function (event) {
        event.preventDefault();

        // Show waiting loader
        document.getElementById('waiting-loader').style.display = 'block';

        const name = document.querySelector('input[name="name"]').value;
        const email = document.querySelector('input[name="email"]').value;
        const contact = document.querySelector('input[name="contact"]').value;

        // Send visitor details to the server
        fetch('/visitor/start-chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ name, email, contact }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Hide the form and show the chat interface
                document.getElementById('waiting-loader').style.display = 'none';
                document.getElementById('start-chat-form').style.display = 'none';
                document.getElementById('chat-interface').style.display = 'block';
                document.getElementById('chat-status').innerText = 'Connected with ' + data.agent_name;

                const newChatId = data.chat_id;
                const channel = pusher.subscribe('chat.' + newChatId);

                // Listen for incoming messages
                channel.bind('message.sent', function (data) {
                    const chatMessages = document.getElementById('chat-messages');
                    const messageDiv = document.createElement('div');
                    messageDiv.className = data.sender === 'You' ? 'message visitor-message' : 'message agent-message';
                    messageDiv.innerHTML = `
                        <div class="message-text">${data.sender}: ${data.message}</div>
                        <div class="message-time">${new Date().toLocaleTimeString()}</div>
                    `;
                    chatMessages.appendChild(messageDiv);
                    // Scroll to the bottom of the chat messages
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                });

                // Listen for chat ended event
                channel.bind('chat.ended', function(data) {
                    console.log('Chat ended event received:', data); // Debugging

                    // Disable message input and send button
                    const messageInput = document.getElementById('message-input');
                    const sendButton = document.getElementById('send-message');
                    if (messageInput && sendButton) {
                        messageInput.disabled = true;
                        sendButton.disabled = true;
                    }

                    // Display a notification
                    const chatMessages = document.getElementById('chat-messages');
                    const notification = document.createElement('div');
                    notification.className = 'text-center text-muted mt-3';
                    notification.textContent = data.message; // "The agent has ended the chat session."
                    chatMessages.appendChild(notification);

                    // Scroll to the bottom
                    chatMessages.scrollTop = chatMessages.scrollHeight;

                    // Clear the chat_id session on the backend
                    fetch('/visitor/clear-session', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            console.log('Session cleared successfully.');
                        }
                    })
                    .catch(error => console.error('Error clearing session:', error));
                });

                // Send message functionality
                document.getElementById('send-message').addEventListener('click', function () {
                    const message = document.getElementById('message-input').value;
                    if (message.trim() === '') return;

                    fetch('/visitor/send-message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ chat_id: newChatId, message }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('message-input').value = '';
                        }
                    });
                });

                // Send message on pressing 'Enter'
                document.getElementById('message-input').addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        document.getElementById('send-message').click();
                    }
                });
            } else if (data.status === 'waiting') {
                document.getElementById('chat-status').innerText = data.message;
            }
        });
    });

    // Leave Chat Button
    document.getElementById('leave-chat').addEventListener('click', function () {
        fetch('/visitor/leave-chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload(); // Refresh the page to reset the chat
            }
        });
    });
});
</script>
@endsection