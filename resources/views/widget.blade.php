<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('laracord-live-chat.widget.title', 'Live Chat Support') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Chat widget styles */
        #laracord-chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            z-index: 9999;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .chat-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
        }

        .message.customer {
            align-items: flex-end;
        }

        .message.agent {
            align-items: flex-start;
        }

        .message-bubble {
            max-width: 80%;
            padding: 10px 14px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.customer .message-bubble {
            background: #007bff;
            color: white;
        }

        .message.agent .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #e9ecef;
        }

        .message-sender {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
            padding: 0 14px;
        }

        .message-time {
            font-size: 11px;
            color: #6c757d;
            margin-top: 4px;
            padding: 0 14px;
        }

        .chat-input-area {
            padding: 16px;
            border-top: 1px solid #e9ecef;
            background: white;
        }

        .chat-input-form {
            display: flex;
            gap: 8px;
        }

        .chat-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #e9ecef;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
        }

        .chat-input:focus {
            border-color: #007bff;
        }

        .chat-send-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .chat-send-btn:hover {
            background: #0056b3;
        }

        .chat-send-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .chat-intro {
            padding: 16px;
            text-align: center;
            background: white;
        }

        .chat-intro h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 16px;
        }

        .chat-intro p {
            margin: 0 0 16px 0;
            color: #6c757d;
            font-size: 14px;
        }

        .chat-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chat-form input {
            padding: 10px 14px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }

        .chat-form button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .chat-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-toggle:hover {
            transform: scale(1.05);
        }

        .hidden {
            display: none !important;
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 8px;
        }

        .typing-indicator {
            padding: 8px 16px;
            font-style: italic;
            color: #6c757d;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            #laracord-chat-widget {
                width: calc(100vw - 40px);
                height: calc(100vh - 40px);
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Chat Toggle Button -->
    <button id="chat-toggle" class="chat-toggle">
        💬
    </button>

    <!-- Chat Widget -->
    <div id="laracord-chat-widget" class="hidden">
        <div class="chat-header">
            <div>
                <span class="status-indicator"></span>
                <h3>{{ config('laracord-live-chat.widget.title', 'Live Chat Support') }}</h3>
            </div>
            <button id="chat-close" class="chat-close">&times;</button>
        </div>

        <!-- Chat Introduction Form -->
        <div id="chat-intro" class="chat-intro">
            <h4>Welcome!</h4>
            <p>{{ config('laracord-live-chat.widget.welcome_message', 'Hello! How can we help you today?') }}</p>
            <form id="chat-form" class="chat-form">
                <input type="text" id="customer-name" placeholder="Your name (optional)" maxlength="255">
                <input type="email" id="customer-email" placeholder="Your email (optional)" maxlength="255">
                <button type="submit">Start Chat</button>
            </form>
        </div>

        <!-- Chat Messages Area -->
        <div id="chat-messages" class="chat-messages hidden"></div>

        <!-- Chat Input Area -->
        <div id="chat-input-area" class="chat-input-area hidden">
            <form id="message-form" class="chat-input-form">
                <input type="text" id="message-input" class="chat-input" 
                       placeholder="{{ config('laracord-live-chat.widget.placeholder', 'Type your message...') }}" 
                       maxlength="2000" required>
                <button type="submit" id="send-btn" class="chat-send-btn">Send</button>
            </form>
        </div>
    </div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        class LaracordChat {
            constructor() {
                this.sessionId = null;
                this.pusher = null;
                this.channel = null;
                this.isConnected = false;
                this.apiBaseUrl = '{{ url("api/laracord-chat") }}';
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                this.initializeElements();
                this.setupEventListeners();
                this.setupPusher();
            }

            initializeElements() {
                this.widget = document.getElementById('laracord-chat-widget');
                this.toggle = document.getElementById('chat-toggle');
                this.closeBtn = document.getElementById('chat-close');
                this.intro = document.getElementById('chat-intro');
                this.messages = document.getElementById('chat-messages');
                this.inputArea = document.getElementById('chat-input-area');
                this.chatForm = document.getElementById('chat-form');
                this.messageForm = document.getElementById('message-form');
                this.messageInput = document.getElementById('message-input');
                this.sendBtn = document.getElementById('send-btn');
            }

            setupEventListeners() {
                this.toggle.addEventListener('click', () => this.toggleWidget());
                this.closeBtn.addEventListener('click', () => this.hideWidget());
                this.chatForm.addEventListener('submit', (e) => this.startSession(e));
                this.messageForm.addEventListener('submit', (e) => this.sendMessage(e));
            }

            setupPusher() {
                if (typeof Pusher !== 'undefined') {
                    this.pusher = new Pusher('{{ config("laracord-live-chat.pusher.key") }}', {
                        cluster: '{{ config("laracord-live-chat.pusher.cluster") }}'
                    });
                }
            }

            toggleWidget() {
                if (this.widget.classList.contains('hidden')) {
                    this.showWidget();
                } else {
                    this.hideWidget();
                }
            }

            showWidget() {
                this.widget.classList.remove('hidden');
                this.toggle.classList.add('hidden');
            }

            hideWidget() {
                this.widget.classList.add('hidden');
                this.toggle.classList.remove('hidden');
            }

            async startSession(e) {
                e.preventDefault();
                
                const name = document.getElementById('customer-name').value;
                const email = document.getElementById('customer-email').value;

                try {
                    const response = await fetch(`${this.apiBaseUrl}/sessions`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({ name, email })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.sessionId = data.session_id;
                        this.switchToChatMode();
                        this.connectToChannel();
                        this.addSystemMessage('Chat session started. An agent will be with you shortly.');
                    } else {
                        alert('Failed to start chat session. Please try again.');
                    }
                } catch (error) {
                    console.error('Error starting chat session:', error);
                    alert('Failed to start chat session. Please try again.');
                }
            }

            switchToChatMode() {
                this.intro.classList.add('hidden');
                this.messages.classList.remove('hidden');
                this.inputArea.classList.remove('hidden');
                this.messageInput.focus();
            }

            connectToChannel() {
                if (this.pusher && this.sessionId) {
                    this.channel = this.pusher.subscribe(`chat-session.${this.sessionId}`);
                    this.channel.bind('message.sent', (data) => {
                        this.addMessage(data);
                    });
                    
                    this.channel.bind('session.closed', () => {
                        this.addSystemMessage('Chat session has been closed.');
                        this.disableInput();
                    });
                }
            }

            async sendMessage(e) {
                e.preventDefault();
                
                const message = this.messageInput.value.trim();
                if (!message || !this.sessionId) return;

                this.sendBtn.disabled = true;
                this.messageInput.disabled = true;

                try {
                    const response = await fetch(`${this.apiBaseUrl}/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({
                            session_id: this.sessionId,
                            message: message,
                            name: document.getElementById('customer-name').value
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.messageInput.value = '';
                    } else {
                        alert(data.message || 'Failed to send message');
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message. Please try again.');
                } finally {
                    this.sendBtn.disabled = false;
                    this.messageInput.disabled = false;
                    this.messageInput.focus();
                }
            }

            addMessage(data) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${data.sender_type}`;

                const senderDiv = document.createElement('div');
                senderDiv.className = 'message-sender';
                senderDiv.textContent = data.sender_name || (data.sender_type === 'customer' ? 'You' : 'Support Agent');

                const bubbleDiv = document.createElement('div');
                bubbleDiv.className = 'message-bubble';
                bubbleDiv.textContent = data.message;

                const timeDiv = document.createElement('div');
                timeDiv.className = 'message-time';
                timeDiv.textContent = new Date(data.created_at).toLocaleTimeString();

                messageDiv.appendChild(senderDiv);
                messageDiv.appendChild(bubbleDiv);
                messageDiv.appendChild(timeDiv);

                this.messages.appendChild(messageDiv);
                this.scrollToBottom();
            }

            addSystemMessage(message) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message system';
                messageDiv.style.textAlign = 'center';
                messageDiv.style.fontStyle = 'italic';
                messageDiv.style.color = '#6c757d';
                messageDiv.style.fontSize = '12px';
                messageDiv.style.margin = '10px 0';
                messageDiv.textContent = message;

                this.messages.appendChild(messageDiv);
                this.scrollToBottom();
            }

            scrollToBottom() {
                this.messages.scrollTop = this.messages.scrollHeight;
            }

            disableInput() {
                this.messageInput.disabled = true;
                this.sendBtn.disabled = true;
                this.messageInput.placeholder = 'Chat session ended';
            }
        }

        // Initialize chat when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new LaracordChat();
        });
    </script>
</body>
</html>
