<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Auth required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type']; // 'Admin', 'Employer', 'Employee'
$action = $_REQUEST['action'] ?? '';

try {
    // ADMIN ACTIONS
    if ($user_type === 'Admin') {

        // List All Chats
        if ($action === 'list_chats') {
            $sql = "SELECT c.*,
                    (SELECT COUNT(*) FROM support_messages m WHERE m.chat_id = c.id AND m.is_read = 0 AND m.sender_type = 'user') as unread,
                    (SELECT message FROM support_messages m WHERE m.chat_id = c.id ORDER BY id DESC LIMIT 1) as last_msg
                    FROM support_chats c
                    ORDER BY c.updated_at DESC";
            $chats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            // Enrich with user names (requires querying user_table/profiles - simplified here using subqueries or joins in real app)
            // For now, fetch name on fly or just show ID
            foreach($chats as &$c) {
                if($c['user_type'] == 'Employer') {
                    $name = $pdo->query("SELECT employer_name FROM employer_profile WHERE link_to_user = {$c['user_id']}")->fetchColumn();
                } else {
                    $name = $pdo->query("SELECT employee_full_name FROM employee_profile_seeker WHERE link_to_user = {$c['user_id']}")->fetchColumn();
                }
                $c['user_name'] = $name ?: "User #{$c['user_id']}";
            }
            echo json_encode(['chats' => $chats]);
            exit();
        }

        // Send as Admin
        if ($action === 'send') {
            $chat_id = $_POST['chat_id'];
            $msg = trim($_POST['message']);
            if ($msg) {
                $stmt = $pdo->prepare("INSERT INTO support_messages (chat_id, sender_type, message) VALUES (?, 'admin', ?)");
                $stmt->execute([$chat_id, $msg]);
                $pdo->prepare("UPDATE support_chats SET updated_at = NOW() WHERE id = ?")->execute([$chat_id]);
            }
            echo json_encode(['success' => true]);
            exit();
        }

        // Toggle System
        if ($action === 'toggle_system') {
            $val = $_POST['enabled']; // 1 or 0
            $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'chat_enabled'")->execute([$val]);
            echo json_encode(['success' => true]);
            exit();
        }
    }

    // USER ACTIONS (And shared 'fetch_messages')

    // Check if system enabled
    $sys = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'chat_enabled'")->fetchColumn();
    if ($sys != '1' && $user_type !== 'Admin') {
        echo json_encode(['error' => 'Chat Unavailable']);
        exit();
    }

    if ($action === 'init') {
        // Find or Create Chat for User
        $stmt = $pdo->prepare("SELECT id FROM support_chats WHERE user_id = ? AND user_type = ? LIMIT 1");
        $stmt->execute([$user_id, $user_type]);
        $chat_id = $stmt->fetchColumn();

        if (!$chat_id) {
            $stmt = $pdo->prepare("INSERT INTO support_chats (user_id, user_type) VALUES (?, ?)");
            $stmt->execute([$user_id, $user_type]);
            $chat_id = $pdo->lastInsertId();
        }
        echo json_encode(['chat_id' => $chat_id]);
        exit();
    }

    if ($action === 'fetch_messages') {
        $chat_id = $_GET['chat_id'];

        // Security: Ensure User owns this chat OR is Admin
        if ($user_type !== 'Admin') {
            $check = $pdo->prepare("SELECT id FROM support_chats WHERE id = ? AND user_id = ?");
            $check->execute([$chat_id, $user_id]);
            if (!$check->fetch()) { echo json_encode([]); exit(); }
        }

        // Mark read if viewer is recipient
        if ($user_type === 'Admin') {
            $pdo->prepare("UPDATE support_messages SET is_read = 1 WHERE chat_id = ? AND sender_type = 'user'")->execute([$chat_id]);
        } else {
            $pdo->prepare("UPDATE support_messages SET is_read = 1 WHERE chat_id = ? AND sender_type = 'admin'")->execute([$chat_id]);
        }

        $stmt = $pdo->prepare("SELECT * FROM support_messages WHERE chat_id = ? ORDER BY created_at ASC");
        $stmt->execute([$chat_id]);
        echo json_encode(['messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit();
    }

    if ($action === 'send_user') {
        $chat_id = $_POST['chat_id'];
        $msg = trim($_POST['message']);

        // Verify Ownership
        $check = $pdo->prepare("SELECT id FROM support_chats WHERE id = ? AND user_id = ?");
        $check->execute([$chat_id, $user_id]);
        if (!$check->fetch()) die(json_encode(['error' => 'Access Denied']));

        if ($msg) {
            $stmt = $pdo->prepare("INSERT INTO support_messages (chat_id, sender_type, message) VALUES (?, 'user', ?)");
            $stmt->execute([$chat_id, $msg]);
            $pdo->prepare("UPDATE support_chats SET updated_at = NOW(), status = 'open' WHERE id = ?")->execute([$chat_id]);
        }
        echo json_encode(['success' => true]);
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>