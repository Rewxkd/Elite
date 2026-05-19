<?php
session_start();
include '../includes/db_connect.php';

header('Content-Type: application/json');
ini_set('serialize_precision', '-1');

const NOTIFICATIONS_VIP_HOURLY_AMOUNT = 1000.00;
const NOTIFICATIONS_VIP_CLAIM_COOLDOWN = 3600;

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

function notifications_respond($payload) {
    echo json_encode($payload);
    exit;
}

function notifications_money($amount) {
    return '$' . number_format((float)$amount, 2);
}

function notifications_last_claim($conn, $user_id, $claim_type) {
    $stmt = $conn->prepare("
        SELECT created_at, TIMESTAMPDIFF(SECOND, created_at, CURRENT_TIMESTAMP) AS elapsed_seconds
        FROM vip_claims
        WHERE user_id = ? AND claim_type = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('is', $user_id, $claim_type);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}

function notifications_latest_by_type($conn, $user_id, $type) {
    $stmt = $conn->prepare("
        SELECT notification_id, amount, is_read, created_at
        FROM notifications
        WHERE user_id = ? AND type = ?
        ORDER BY created_at DESC, notification_id DESC
        LIMIT 1
    ");
    $stmt->bind_param('is', $user_id, $type);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}

function notifications_after_reset($notification, $reset_after) {
    if (!$notification) return false;
    if (!$reset_after) return true;

    $notificationTime = strtotime($notification['created_at'] ?? '') ?: 0;
    $resetTime = strtotime($reset_after) ?: 0;

    return $notificationTime > $resetTime;
}

function notifications_insert($conn, $user_id, $type, $title, $message, $action_key, $amount) {
    $amountValue = (float)$amount;
    $stmt = $conn->prepare('
        INSERT INTO notifications (user_id, type, title, message, action_key, amount)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->bind_param('issssd', $user_id, $type, $title, $message, $action_key, $amountValue);
    $stmt->execute();
    $stmt->close();
}

function notifications_update_availability($conn, $user_id, $notification_id, $title, $message, $action_key, $amount, $wake_unread) {
    $amountValue = (float)$amount;

    if ($wake_unread) {
        $stmt = $conn->prepare('
            UPDATE notifications
            SET title = ?, message = ?, action_key = ?, amount = ?, is_read = 0, created_at = CURRENT_TIMESTAMP
            WHERE notification_id = ? AND user_id = ?
        ');
        $stmt->bind_param('sssdii', $title, $message, $action_key, $amountValue, $notification_id, $user_id);
    } else {
        $stmt = $conn->prepare('
            UPDATE notifications
            SET title = ?, message = ?, action_key = ?, amount = ?
            WHERE notification_id = ? AND user_id = ?
        ');
        $stmt->bind_param('sssdii', $title, $message, $action_key, $amountValue, $notification_id, $user_id);
    }

    $stmt->execute();
    $stmt->close();
}

function notifications_sync_availability($conn, $user_id, $type, $title, $message, $action_key, $amount, $reset_after = null) {
    $latest = notifications_latest_by_type($conn, $user_id, $type);

    if (!$latest || !notifications_after_reset($latest, $reset_after)) {
        notifications_insert($conn, $user_id, $type, $title, $message, $action_key, $amount);
        return;
    }

    $existingAmount = (float)($latest['amount'] ?? 0);
    $isUnread = (int)($latest['is_read'] ?? 0) === 0;
    $amountIncreased = (float)$amount > ($existingAmount + 0.009);

    if ($isUnread || $amountIncreased) {
        notifications_update_availability(
            $conn,
            $user_id,
            (int)$latest['notification_id'],
            $title,
            $message,
            $action_key,
            $amount,
            !$isUnread && $amountIncreased
        );
    }
}

function notifications_mark_type_read($conn, $user_id, $type) {
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = ? AND is_read = 0');
    $stmt->bind_param('is', $user_id, $type);
    $stmt->execute();
    $stmt->close();
}

function notifications_delete_type($conn, $user_id, $type) {
    $stmt = $conn->prepare('DELETE FROM notifications WHERE user_id = ? AND type = ?');
    $stmt->bind_param('is', $user_id, $type);
    $stmt->execute();
    $stmt->close();
}

function notifications_sync_system($conn, $user_id) {
    $lastHourly = notifications_last_claim($conn, $user_id, 'hourly');
    $hourlyRemaining = 0;

    if ($lastHourly && !empty($lastHourly['created_at'])) {
        $elapsed = max(0, (int)($lastHourly['elapsed_seconds'] ?? 0));
        $hourlyRemaining = max(0, NOTIFICATIONS_VIP_CLAIM_COOLDOWN - $elapsed);
    }

    if ($hourlyRemaining <= 0) {
        notifications_sync_availability(
            $conn,
            $user_id,
            'vip_hourly_available',
            'VIP bonus ready',
            'Your ' . notifications_money(NOTIFICATIONS_VIP_HOURLY_AMOUNT) . ' VIP bonus is ready.',
            'vip',
            NOTIFICATIONS_VIP_HOURLY_AMOUNT,
            $lastHourly['created_at'] ?? null
        );
    } else {
        notifications_mark_type_read($conn, $user_id, 'vip_hourly_available');
    }

    notifications_delete_type($conn, $user_id, 'rakeback_available');
}

function notifications_unread_count($conn, $user_id) {
    $stmt = $conn->prepare('SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['count'] ?? 0);
}

function notifications_list($conn, $user_id) {
    $items = [];
    $stmt = $conn->prepare('
        SELECT notification_id, type, title, message, action_key, amount, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY is_read ASC, created_at DESC, notification_id DESC
        LIMIT 20
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'notification_id' => (int)$row['notification_id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'action_key' => $row['action_key'],
            'amount' => $row['amount'] === null ? null : (float)$row['amount'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
        ];
    }

    $stmt->close();

    return $items;
}

function notifications_payload($conn, $user_id) {
    notifications_sync_system($conn, $user_id);

    return [
        'success' => true,
        'unread_count' => notifications_unread_count($conn, $user_id),
        'notifications' => notifications_list($conn, $user_id),
    ];
}

if (!$user_id) {
    notifications_respond(['success' => false, 'message' => 'Please log in first.', 'unread_count' => 0, 'notifications' => []]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $notification_id = (int)($_POST['notification_id'] ?? 0);

        if ($notification_id > 0) {
            $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND notification_id = ?');
            $stmt->bind_param('ii', $user_id, $notification_id);
            $stmt->execute();
            $stmt->close();
        }

        notifications_respond(notifications_payload($conn, $user_id));
    }

    if ($action === 'mark_all_read') {
        $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();

        notifications_respond(notifications_payload($conn, $user_id));
    }

    notifications_respond(['success' => false, 'message' => 'Invalid action.']);
}

notifications_respond(notifications_payload($conn, $user_id));
?>
