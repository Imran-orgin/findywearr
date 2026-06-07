<?php if (isset($_SESSION['user_id'])): ?>

<!-- Chatbot Widget -->
<div id="chatWidget" style="position:fixed; bottom:20px; right:20px; z-index:9999;">

    <!-- Chat Button -->
    <button id="chatToggle" onclick="toggleChat()"
        style="width:60px; height:60px; border-radius:50%; border:none;
        background:linear-gradient(135deg, #667eea, #764ba2);
        color:white; font-size:1.5rem; cursor:pointer;
        box-shadow:0 4px 20px rgba(102,126,234,0.5);
        transition: all 0.3s ease;">
        <i class="fas fa-comments" id="chatIcon"></i>
    </button>

    <!-- Unread Badge -->
    <span id="chatBadge"
        style="position:absolute; top:-5px; right:-5px;
        background:red; color:white; border-radius:50%;
        width:20px; height:20px; font-size:0.7rem;
        display:none; align-items:center; justify-content:center;">
        1
    </span>

    <!-- Chat Box -->
    <div id="chatBox"
        style="display:none; position:absolute; bottom:70px; right:0;
        width:320px; background:white; border-radius:15px;
        box-shadow:0 10px 40px rgba(0,0,0,0.2); overflow:hidden;">

        <!-- Chat Header -->
        <div style="background:linear-gradient(135deg,#667eea,#764ba2);
            padding:15px; color:white;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div style="width:35px;height:35px;background:rgba(255,255,255,0.2);
                        border-radius:50%;display:flex;align-items:center;
                        justify-content:center;margin-right:10px;">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <p class="fw-bold mb-0" style="font-size:0.9rem;">
                            FindyWear AI
                        </p>
                        <small style="opacity:0.8;">
                            <i class="fas fa-circle" style="font-size:0.5rem;color:#2ecc71;"></i>
                            Online
                        </small>
                    </div>
                </div>
                <button onclick="toggleChat()"
                    style="background:none;border:none;color:white;font-size:1.2rem;cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Chat Messages -->
        <div id="chatMessages"
            style="height:300px;overflow-y:auto;padding:15px;background:#f8f9ff;">

            <!-- Welcome Message -->
            <div class="chat-msg bot-msg mb-3">
                <div style="display:flex;align-items:flex-start;gap:8px;">
                    <div style="width:30px;height:30px;background:linear-gradient(135deg,#667eea,#764ba2);
                        border-radius:50%;display:flex;align-items:center;
                        justify-content:center;flex-shrink:0;">
                        <i class="fas fa-robot text-white" style="font-size:0.7rem;"></i>
                    </div>
                    <div style="background:white;padding:10px 12px;border-radius:0 12px 12px 12px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.08);max-width:80%;font-size:0.85rem;">
                        Hi <?php echo htmlspecialchars($_SESSION['name']); ?>! 👋 
                        I'm FindyWear AI. How can I help you today?
                        <br><br>
                        <small style="color:#888;">You can ask me about:</small>
                        <br>
                        <small>📦 Order status</small><br>
                        <small>🏪 Nearby shops</small><br>
                        <small>↩️ Returns & refunds</small>
                    </div>
                </div>
            </div>

        </div>

        <!-- Quick Replies -->
        <div style="padding:8px 15px;background:white;border-top:1px solid #eee;
            display:flex;gap:5px;flex-wrap:wrap;">
            <button onclick="quickReply('Where is my order?')"
                style="background:#f0f0f0;border:none;padding:4px 10px;
                border-radius:20px;font-size:0.75rem;cursor:pointer;">
                📦 My Orders
            </button>
            <button onclick="quickReply('How to return a product?')"
                style="background:#f0f0f0;border:none;padding:4px 10px;
                border-radius:20px;font-size:0.75rem;cursor:pointer;">
                ↩️ Return
            </button>
            <button onclick="quickReply('How does 5km delivery work?')"
                style="background:#f0f0f0;border:none;padding:4px 10px;
                border-radius:20px;font-size:0.75rem;cursor:pointer;">
                🚗 Delivery
            </button>
        </div>

        <!-- Chat Input -->
        <div style="padding:10px 15px;background:white;border-top:1px solid #eee;">
            <div style="display:flex;gap:8px;">
                <input type="text" id="chatInput"
                    placeholder="Type your message..."
                    style="flex:1;border:2px solid #e9ecef;border-radius:25px;
                    padding:8px 15px;font-size:0.85rem;outline:none;"
                    onkeypress="if(event.key==='Enter') sendMessage()"
                    onfocus="this.style.borderColor='#667eea'"
                    onblur="this.style.borderColor='#e9ecef'">
                <button onclick="sendMessage()"
                    style="width:38px;height:38px;border-radius:50%;border:none;
                    background:linear-gradient(135deg,#667eea,#764ba2);
                    color:white;cursor:pointer;flex-shrink:0;">
                    <i class="fas fa-paper-plane" style="font-size:0.8rem;"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
let chatOpen = false;

function toggleChat() {
    chatOpen = !chatOpen;
    const box   = document.getElementById('chatBox');
    const icon  = document.getElementById('chatIcon');
    const badge = document.getElementById('chatBadge');

    box.style.display   = chatOpen ? 'block' : 'none';
    icon.className      = chatOpen ? 'fas fa-times' : 'fas fa-comments';
    badge.style.display = 'none';

    if (chatOpen) {
        document.getElementById('chatInput').focus();
        scrollToBottom();
    }
}

function quickReply(msg) {
    document.getElementById('chatInput').value = msg;
    sendMessage();
}

function sendMessage() {
    const input   = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;

    // Show user message
    addMessage(message, 'user');
    input.value = '';

    // Show typing indicator
    addTyping();

    // Send to API
    fetch('/findywearce/api/chatbot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message=' + encodeURIComponent(message)
    })
    .then(res => res.json())
    .then(data => {
        removeTyping();
        addMessage(data.reply, 'bot');
    })
    .catch(() => {
        removeTyping();
        addMessage('Sorry, something went wrong. Please try again!', 'bot');
    });
}

function addMessage(text, sender) {
    const messages = document.getElementById('chatMessages');
    const isBot    = sender === 'bot';

    const html = isBot ? `
        <div class="chat-msg mb-3">
            <div style="display:flex;align-items:flex-start;gap:8px;">
                <div style="width:30px;height:30px;
                    background:linear-gradient(135deg,#667eea,#764ba2);
                    border-radius:50%;display:flex;align-items:center;
                    justify-content:center;flex-shrink:0;">
                    <i class="fas fa-robot text-white" style="font-size:0.7rem;"></i>
                </div>
                <div style="background:white;padding:10px 12px;
                    border-radius:0 12px 12px 12px;
                    box-shadow:0 2px 8px rgba(0,0,0,0.08);
                    max-width:80%;font-size:0.85rem;">
                    ${text}
                </div>
            </div>
        </div>` : `
        <div class="chat-msg mb-3" style="text-align:right;">
            <div style="display:flex;align-items:flex-end;
                justify-content:flex-end;gap:8px;">
                <div style="background:linear-gradient(135deg,#667eea,#764ba2);
                    color:white;padding:10px 12px;
                    border-radius:12px 12px 0 12px;
                    max-width:80%;font-size:0.85rem;">
                    ${text}
                </div>
            </div>
        </div>`;

    messages.insertAdjacentHTML('beforeend', html);
    scrollToBottom();
}

function addTyping() {
    const messages = document.getElementById('chatMessages');
    messages.insertAdjacentHTML('beforeend', `
        <div id="typingIndicator" class="mb-3">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:30px;height:30px;
                    background:linear-gradient(135deg,#667eea,#764ba2);
                    border-radius:50%;display:flex;align-items:center;
                    justify-content:center;">
                    <i class="fas fa-robot text-white" style="font-size:0.7rem;"></i>
                </div>
                <div style="background:white;padding:10px 15px;
                    border-radius:0 12px 12px 12px;
                    box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                    <span style="display:flex;gap:4px;">
                        <span style="width:6px;height:6px;background:#667eea;
                            border-radius:50%;animation:bounce 1s infinite;"></span>
                        <span style="width:6px;height:6px;background:#667eea;
                            border-radius:50%;animation:bounce 1s infinite 0.2s;"></span>
                        <span style="width:6px;height:6px;background:#667eea;
                            border-radius:50%;animation:bounce 1s infinite 0.4s;"></span>
                    </span>
                </div>
            </div>
        </div>
    `);
    scrollToBottom();
}

function removeTyping() {
    const typing = document.getElementById('typingIndicator');
    if (typing) typing.remove();
}

function scrollToBottom() {
    const messages = document.getElementById('chatMessages');
    messages.scrollTop = messages.scrollHeight;
}
</script>

<style>
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-5px); }
}
</style>

<?php endif; ?>