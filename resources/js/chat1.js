import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

class ChatManager {
    constructor() {
        this.sessionId = null;
        this.initialized = false;
        this.echo = null;
        this.setupEcho();
        this.attachEventListeners();
    }

    setupEcho() {
        this.echo = new Echo({
            broadcaster: "reverb",
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
            enabledTransports: ["ws", "wss"],
            auth: {
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
            },
        });
    
        console.log("Echo initialized for private channel:", this.echo);
    }

    attachEventListeners() {
        // Agent status toggle buttons
        document.querySelectorAll(".status-toggle").forEach((button) => {
            button.addEventListener("click", () =>
                this.updateAgentStatus(button.dataset.status)
            );
        });

        // Message form submission
        const messageForm = document.getElementById("message-form");
        if (messageForm) {
            messageForm.addEventListener("submit", (e) =>
                this.handleMessageSubmit(e)
            );
        }

        // Initial chat form submission
        const initialForm = document.getElementById("initial-form");
        if (initialForm) {
            initialForm.addEventListener("submit", (e) =>
                this.handleInitialSubmit(e)
            );
        }
    }

    async updateAgentStatus(status) {
        try {
            const response = await fetch("/agent/chat/status", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ status }),
            });

            if (response.ok) {
                document.querySelectorAll(".status-toggle").forEach((btn) => {
                    btn.classList.remove("active");
                    if (btn.dataset.status === status) {
                        btn.classList.add("active");
                    }
                });
            }
        } catch (error) {
            console.error("Error updating status:", error);
        }
    }

    async handleMessageSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        // Retrieve the visitor ID from local storage
        const visitorId = localStorage.getItem("visitorId");
        console.log("Visitor ID:", visitorId);

        // Append the visitor ID to the form data
        formData.append("visitor_id", visitorId);

        try {
            const response = await fetch(`/chat/${this.sessionId}/message`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
                
                body: formData,
            });
            console.log(document.querySelector('meta[name="csrf-token"]').content);
            if (response.ok) {
                form.reset();
                const attachment = document.getElementById("attachment");
                if (attachment) {
                    attachment.value = "";
                }
            }
        } catch (error) {
            console.error("Error sending message:", error);
        }
    }

    async handleInitialSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
    
        try {
            const response = await fetch("/chat/initiate", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            });
    
            if (response.ok) {
                const { data, visitor_id } = await response.json();
    
                // Store the visitor ID in local storage
                localStorage.setItem("visitorId", visitor_id);
    
                // Set the session ID
                this.sessionId = data.id;
                console.log("Session ID:", this.sessionId);
    
                // Update UI
                form.style.display = "none";
                const messagesDiv = document.getElementById("chat-messages");
                messagesDiv.innerHTML = `
                    <div class="system-message">
                        ${data.agent ? `Connected to agent ${data.agent.name}` : "Looking for an available agent..."}
                    </div>
                `;
    
                // Show message form
                const messageFormHtml = `
                    <form id="message-form" class="message-form">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                    </form>
                `;
                document.getElementById("chat-form").innerHTML = messageFormHtml;
    
                // Initialize chat
                await this.initializeChat();
    
                // Attach message form listener
                const messageForm = document.getElementById("message-form");
                if (messageForm) {
                    messageForm.addEventListener("submit", (e) =>
                        this.handleMessageSubmit(e)
                    );
                }
            }
        } catch (error) {
            console.error("Error initiating chat:", error);
        }
    }

    async initializeChat() {
        if (this.initialized) return;
    
        try {
            console.log("Initializing chat for session:", this.sessionId);
    
            // Subscribe to the private channel
            const channel = this.echo.private(`chat.${this.sessionId}`);
    
            // Attach listeners
            channel
                .listen("ChatMessageSent", (e) => {
                    console.log("ChatMessageSent event received:", e);
                    this.appendMessage(e.chatMessage);
                })
                .listen("ChatSessionUpdated", (e) => {
                    console.log("ChatSessionUpdated event received:", e);
                    this.handleSessionUpdate(e.chatSession);
                });
    
            this.initialized = true;
        } catch (error) {
            console.error("Error initializing chat:", error);
        }
    }

    appendMessage(message) {
        const messagesContainer = document.getElementById("chat-messages");
        const messageDiv = document.createElement("div");
        messageDiv.className = `chat-message ${
            message.sender_type === "App\\Models\\ChatAgent" ? "agent" : "visitor"
        }`;
        messageDiv.innerHTML = `
            <div class="message-content">
                <div class="message-text">${message.message}</div>
                ${
                    message.attachment_path
                        ? `
                    <div class="message-attachment">
                        <a href="/storage/${message.attachment_path}" target="_blank">View Attachment</a>
                    </div>
                `
                        : ""
                }
                <div class="message-time">${new Date(
                    message.created_at
                ).toLocaleTimeString()}</div>
            </div>
        `;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    handleSessionUpdate(session) {
        if (session.status === "active" && session.agent) {
            document.getElementById("chat-messages").innerHTML += `
                <div class="system-message">
                    Connected to agent ${session.agent.user.name}
                </div>
            `;
        }
    }

    initializeAgentDashboard() {
        this.echo.private(`chat.${this.sessionId}`).listen("ChatSessionStarted", (e) => {
            console.log("ChatSessionStarted event received:", e);
            this.updateSessionsList(e.chatSession);
        });
    }

    updateSessionsList(session) {
        const sessionsList = document.querySelector(".sessions-list");
        if (!sessionsList) return;

        const sessionHtml = `
            <tr>
                <td>${session.visitor_name}</td>
                <td><span class="badge badge-${session.status}">${session.status}</span></td>
                <td>just now</td>
                <td>
                    <a href="/agent/chat/${session.id}" class="btn btn-sm btn-primary">View</a>
                    <button class="btn btn-sm btn-danger end-session" data-session-id="${session.id}">End</button>
                </td>
            </tr>
        `;

        sessionsList.insertAdjacentHTML("afterbegin", sessionHtml);
    }
}

// Initialize chat manager when document is ready
document.addEventListener("DOMContentLoaded", () => {
    window.chatManager = new ChatManager();
    if (window.chatSessionId) {
        chatManager.sessionId = window.chatSessionId; // Set the session ID
    }
    chatManager.initializeAgentDashboard();
});

// Toggle chat box visibility
document.addEventListener("DOMContentLoaded", () => {
    const button = document.querySelector(".chat-widget-button");
    const chatBox = document.querySelector(".chat-widget-box");

    if (button && chatBox) {
        button.addEventListener("click", () => {
            console.log("Chat box toggled");
            chatBox.style.display =
                chatBox.style.display === "none" ? "flex" : "none";
        });
    }
});