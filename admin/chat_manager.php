<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php"); exit();
}

// Fetch System Status
$chatEnabled = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'chat_enabled'")->fetchColumn() == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Chat Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; height: 100vh; overflow: hidden; }
        .chat-layout { display: flex; height: calc(100vh - 60px); margin-top: 20px; }
        .chat-list { width: 350px; background: white; border-right: 1px solid #ddd; overflow-y: auto; }
        .chat-main { flex: 1; display: flex; flex-direction: column; background: white; }
        .chat-header { padding: 15px; border-bottom: 1px solid #ddd; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center; }
        .msg-area { flex: 1; padding: 20px; overflow-y: auto; background: #e5ddd5; }
        .input-area { padding: 15px; border-top: 1px solid #ddd; background: #f0f0f0; }

        .chat-item { padding: 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: 0.2s; }
        .chat-item:hover { background: #f5f6f6; }
        .chat-item.active { background: #e7f3ff; }

        .msg { max-width: 70%; padding: 10px 15px; border-radius: 10px; margin-bottom: 10px; position: relative; }
        .msg-admin { background: #dcf8c6; align-self: flex-end; margin-left: auto; }
        .msg-user { background: white; align-self: flex-start; }
        .time { font-size: 0.7rem; color: #999; margin-top: 5px; display: block; text-align: right; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom px-4" style="height: 60px;">
    <span class="navbar-brand fw-bold"><i class="fas fa-headset me-2 text-primary"></i>Live Support</span>
    <div class="d-flex align-items-center gap-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="sysToggle" <?= $chatEnabled ? 'checked' : '' ?> onchange="toggleSystem(this)">
            <label class="form-check-label small fw-bold" for="sysToggle">Chat System Active</label>
        </div>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Exit</a>
    </div>
</nav>

<div class="container-fluid chat-layout px-0">
    <!-- List -->
    <div class="chat-list" id="chatList">
        <div class="text-center p-4 text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
    </div>

    <!-- Chat Area -->
    <div class="chat-main" id="chatMain">
        <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
            <i class="fas fa-comments fa-3x mb-3"></i>
            <h5>Select a conversation to start</h5>
        </div>
    </div>
</div>

<script>
let currentChatId = null;
let pollInterval = null;

function loadChats() {
    fetch('../actions/chat_api.php?action=list_chats')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('chatList');
            list.innerHTML = '';

            data.chats.forEach(c => {
                const activeClass = c.id == currentChatId ? 'active' : '';
                const badge = c.unread > 0 ? `<span class="badge bg-danger rounded-pill float-end">${c.unread}</span>` : '';

                list.innerHTML += `
                    <div class="chat-item ${activeClass}" onclick="openChat(${c.id}, '${c.user_name}')">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">${c.user_name}</span>
                            <span class="small text-muted">${new Date(c.updated_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                        </div>
                        <div class="small text-muted text-truncate mt-1">${c.last_msg || 'New Chat'}</div>
                        ${badge}
                    </div>
                `;
            });
        });
}

function openChat(id, name) {
    currentChatId = id;
    loadChats(); // Refresh list to clear badge logic visually

    const main = document.getElementById('chatMain');
    main.innerHTML = `
        <div class="chat-header">
            <h6 class="m-0">${name}</h6>
            <button class="btn btn-sm btn-light border"><i class="fas fa-ellipsis-v"></i></button>
        </div>
        <div class="msg-area d-flex flex-column" id="msgArea"></div>
        <div class="input-area d-flex gap-2">
            <input type="text" id="msgInput" class="form-control" placeholder="Type a reply..." onkeypress="if(event.key==='Enter') sendMsg()">
            <button class="btn btn-primary" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
        </div>
    `;

    fetchMessages();
    if(pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(fetchMessages, 3000);
}

function fetchMessages() {
    if(!currentChatId) return;
    fetch('../actions/chat_api.php?action=fetch_messages&chat_id=' + currentChatId)
        .then(r => r.json())
        .then(data => {
            const area = document.getElementById('msgArea');
            const wasAtBottom = area.scrollTop + area.clientHeight >= area.scrollHeight - 50;

            area.innerHTML = data.messages.map(m => `
                <div class="msg ${m.sender_type === 'admin' ? 'msg-admin' : 'msg-user'}">
                    ${m.message}
                    <span class="time">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
            `).join('');

            if(wasAtBottom || area.childElementCount < 10) area.scrollTop = area.scrollHeight;
        });
}

function sendMsg() {
    const input = document.getElementById('msgInput');
    const txt = input.value.trim();
    if(!txt) return;

    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('chat_id', currentChatId);
    formData.append('message', txt);

    fetch('../actions/chat_api.php', { method: 'POST', body: formData })
        .then(() => {
            input.value = '';
            fetchMessages();
        });
}

function toggleSystem(el) {
    const formData = new FormData();
    formData.append('action', 'toggle_system');
    formData.append('enabled', el.checked ? 1 : 0);
    fetch('../actions/chat_api.php', { method: 'POST', body: formData });
}

// Init
loadChats();
setInterval(loadChats, 10000); // Refresh list every 10s
</script>

</body>
</html>
