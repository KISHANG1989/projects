<?php
require_once __DIR__ . '/../config/db.php';

function createNotification($user_id, $message, $link = '#') {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $message, $link]);
}

function getUnreadNotifications($user_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function markNotificationRead($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

// Handle AJAX mark read
if (isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user_id'])) {
        markNotificationRead((int)$_POST['id']);
        echo 'ok';
    }
    exit;
}
