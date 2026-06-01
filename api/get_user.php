<?php
require_once '../db.php';
sessionStart();

header('Content-Type: application/json');

if (!currentUser()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id=? AND onboarding_done=1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Decode JSON fields
$user['interests']           = json_decode($user['interests'] ?? '[]', true) ?: [];
$user['hobbies']             = json_decode($user['hobbies'] ?? '[]', true) ?: [];
$user['photos']              = json_decode($user['photos'] ?? '[]', true) ?: [];
$user['prompts']             = json_decode($user['prompts'] ?? '[]', true) ?: [];
$user['partner_preferences'] = json_decode($user['partner_preferences'] ?? '[]', true) ?: [];

// Remove sensitive fields
unset($user['password_hash'], $user['email'], $user['phone']);

echo json_encode($user);
