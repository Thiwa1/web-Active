<?php
// Ensure DB connection exists
if(!isset($pdo)) return;

$chatActive = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'chat_enabled'")->fetchColumn() == '1';
if(!$chatActive) return;
?>

<!-- Chat Widget -->
<style>
    .chat-float { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
    .chat-btn {
        width: 60px; height: 60px;
        background: #4f46e5; color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        transition: 0.3s;
    }
    .chat-btn:hover { transform: scale(1.1); }

    .chat-box {
        position: fixed; bottom: 100px; right: 30px; width: 350px; height: 450px;
        background: white; border-radius: 16px;
        box-shadow: 0 5px 30px rgba(0,0,0,0.15);
        display: none; flex-direction: column; overflow: hidden;
        border: 1px solid #e2e8f0;
        z-index: 9999;
    }
    .chat-box.open { display: flex; }

    .cw-header { background: #4f46e5; color: white; padding: 15px; display: flex; justify-content: space-between; }
    .cw-body { flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; font-size: 0.9rem; }
    .cw-footer { padding: 10px; background: white; border-top: 1px solid #eee; display: flex; gap: 5px; }

    .cw-msg { max-width: 80%; padding: 8px 12px; border-radius: 12px; margin-bottom: 8px; font-size: 0.85rem; }
    .cw-msg-user { background: #4f46e5; color: white; align-self: flex-end; margin-left: auto; border-bottom-right-radius: 2px; }
    .cw-msg-admin { background: #e2e8f0; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 2px; }
</style>

<div class="chat-float">
    <div class="chat-btn" onclick="toggleChat()">
        <i class="fas fa-comments fa-lg"></i>
    </div>
</div>

<div class="chat-box" id="cwBox">
    <div class="cw-header">
        <span class="fw-bold"><i class="fas fa-headset me-2"></i>Live Support</span>
        <i class="fas fa-times cursor-pointer" onclick="toggleChat()"></i>
    </div>
    <div class="cw-body d-flex flex-column" id="cwBody">
        <div class="text-center text-muted mt-5">
            <i class="fas fa-spinner fa-spin"></i> Connecting...
        </div>
    </div>
    <div class="cw-footer">
        <input type="text" id="cwInput" class="form-control form-control-sm" placeholder="Type message..." onkeypress="if(event.key==='Enter') cwSend()">
        <button class="btn btn-primary btn-sm" onclick="cwSend()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
let cwChatId = null;
let cwInterval = null;
let cwOpen = false;

function toggleChat() {
    const box = document.getElementById('cwBox');
    cwOpen = !cwOpen;
    box.classList.toggle('open');

    if(cwOpen && !cwChatId) {
        initChat();
    }
}

function initChat() {
    fetch('../actions/chat_api.php?action=init')
        .then(r => r.json())
        .then(data => {
            if(data.error) {
                document.getElementById('cwBody').innerHTML = '<div class="text-center text-danger p-3">' + data.error + '</div>';
                return;
            }
            cwChatId = data.chat_id;
            fetchChatMsgs();
            cwInterval = setInterval(fetchChatMsgs, 4000);
        });
}

function fetchChatMsgs() {
    if(!cwChatId) return;
    fetch('../actions/chat_api.php?action=fetch_messages&chat_id=' + cwChatId)
        .then(r => r.json())
        .then(data => {
            const area = document.getElementById('cwBody');
            if(!data.messages) return;

            const wasAtBottom = area.scrollTop + area.clientHeight >= area.scrollHeight - 50;

            if (data.messages.length === 0) {
                area.innerHTML = '<div class="text-center text-muted mt-4"><small>Start a conversation with us!</small></div>';
                return;
            }

            area.innerHTML = data.messages.map(m => `
                <div class="cw-msg ${m.sender_type === 'user' ? 'cw-msg-user' : 'cw-msg-admin'}">
                    ${m.message}
                </div>
            `).join('');

            if(wasAtBottom) area.scrollTop = area.scrollHeight;
        });
}

function cwSend() {
    const input = document.getElementById('cwInput');
    const txt = input.value.trim();
    if(!txt || !cwChatId) return;

    const formData = new FormData();
    formData.append('action', 'send_user');
    formData.append('chat_id', cwChatId);
    formData.append('message', txt);

    // Optimistic UI
    const area = document.getElementById('cwBody');
    area.innerHTML += `<div class="cw-msg cw-msg-user">${txt}</div>`;
    area.scrollTop = area.scrollHeight;
    input.value = '';

    fetch('../actions/chat_api.php', { method: 'POST', body: formData })
        .then(() => fetchChatMsgs());
}
</script>
