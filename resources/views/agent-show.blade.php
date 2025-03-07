@extends('layouts.app')

@section('content')
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Chat with {{ $chat->visitor->name }}
                    <span class="badge bg-{{ $chat->status === 'active' ? 'success' : 'warning text-dark' }} ms-2">
                        {{ ucfirst($chat->status) }}
                    </span>
                </h3>
            </div>
            <div class="card-body chat-container" id="chat-messages">
                @foreach ($chat->messages as $message)
                    <div class="chat-message p-3 rounded {{ $message->sender_type === 'agent' ? 'agent' : 'visitor' }}">
                        <div class="text-muted mb-1">
                            {{ $message->sender_type === 'agent' ? 'You' : $chat->visitor->name }}
                            - {{ $message->created_at->diffForHumans() }}
                        </div>
                        <div class="message-text">{{ $message->message }}</div>
                    </div>
                @endforeach
            </div>

            <div class="card-footer">
                @if ($chat->status === 'active')
                    <form id="message-form" class="d-flex gap-2">
                        <input type="text" id="message" class="form-control" placeholder="Type your message..." autocomplete="off">
                        <button type="button" id="agent-send-message" class="btn btn-primary">Send</button>
                        <button type="button" id="end-chat-session" class="btn btn-danger">End Session</button>
                    </form>
                @else
                    <p class="text-muted">This chat session has ended. You can no longer send messages.</p>
                @endif
            </div>
        </div>
    </div>
    <style>
        /* Chat container */
        .chat-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 10px;
            overflow-y: auto;
            height: 400px; /* Adjust height as needed */
        }
    
        /* Base message styling */
        .chat-message {
            max-width: 70%;
            padding: 10px;
            border-radius: 10px;
            position: relative;
        }
    
        /* Agent messages (right side) */
        .chat-message.agent {
            align-self: flex-end;
            background-color: #0d6efd; /* Blue color for agent messages */
            color: white;
        }
    
        /* Visitor messages (left side) */
        .chat-message.visitor {
            align-self: flex-start;
            background-color: #f1f1f1; /* Light gray color for visitor messages */
            color: black;
        }
    
        /* Message text styling */
        .message-text {
            word-wrap: break-word;
            font-size: 14px;
        }
    
        /* Timestamp styling */
        .text-muted {
            font-size: 12px;
            color: #666;
        }
    </style>
    <!-- Pass necessary data to JavaScript -->
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const agentId = {{ auth()->user()->id }}; // Logged-in agent's ID
            const chatId = {{ $chat->id }}; // Chat ID

            // Initialize Pusher
            const pusher = new Pusher("73fd4859f7ea3b680067", {
                cluster: "ap1",
                encrypted: true
            });

            // Subscribe to the chat channel
            const channel = pusher.subscribe('chat.' + chatId);

            // Listen for new messages
            channel.bind('message.sent', function(data) {
                appendMessage(data.message, data.senderType); // Show received message
            });

            // Send message to visitor
            // Send message to visitor
            document.getElementById('agent-send-message').addEventListener('click', function () {
                const messageInput = document.getElementById('message');
                const message = messageInput.value.trim();
            
                if (message === '') return; // Prevent empty messages
            
                fetch('/agent/send-message', {
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
                        messageInput.value = ''; // Clear input field
                    }
                })
                .catch(error => console.error('Error sending message:', error));
            });

            // Function to append message to chat
            function appendMessage(message, senderType) {
                
                const chatMessages = document.getElementById('chat-messages');
                const messageElement = document.createElement('div');
                messageElement.classList.add('chat-message', 'p-3', 'rounded', senderType);
                messageElement.innerHTML = `
                    <div class="text-muted mb-1">
                        ${senderType === 'agent' ? 'You' : '{{ $chat->visitor->name }}'} - Just now
                    </div>
                    <div class="message-text">${message}</div>
                `;
                chatMessages.appendChild(messageElement);
                chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll to latest message
            }
            // Listen for chat ended event
            channel.bind('chat.ended', function(data) {
                // Disable message input and send button
                const messageInput = document.getElementById('message');
                const sendButton = document.getElementById('agent-send-message');
                const endSessionButton = document.getElementById('end-chat-session');
            
                if (messageInput && sendButton && endSessionButton) {
                    messageInput.disabled = true;
                    sendButton.disabled = true;
                    endSessionButton.disabled = true;
                }
            
                // Display a message indicating the session has ended
                const chatMessages = document.getElementById('chat-messages');
                const endedMessage = document.createElement('div');
                endedMessage.className = 'text-center text-muted mt-3';
                endedMessage.textContent = 'The visitor has ended the chat session.';
                chatMessages.appendChild(endedMessage);
            
                // Scroll to the bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
            // Listen for visitor connected event
            channel.bind('visitor.connected', function(data) {
                // Display a notification
                const chatMessages = document.getElementById('chat-messages');
                const notification = document.createElement('div');
                notification.className = 'text-center text-muted mt-3';
                notification.textContent = `${data.message} Visitor: ${data.visitor_name}`;
                chatMessages.appendChild(notification);
                // Scroll to the bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
        });
        document.getElementById('end-chat-session').addEventListener('click', function () {
            fetch('/agent/end-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ chat_id: {{ $chat->id }} }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Chat session ended successfully.');
                    window.location.reload(); // Refresh the page to reflect the changes
                }
            })
            .catch(error => console.error('Error ending chat session:', error));
        });
        document.addEventListener('DOMContentLoaded', function () {
    // Scroll chat container to the bottom on page load
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
    </script>
@endsection
