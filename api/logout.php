<?php
require_once '../db.php';
sessionStart();

if (isset($_SESSION['user'])) {
    $db = getDB();
    $db->prepare("UPDATE users SET is_online=0 WHERE id=?")->execute([$_SESSION['user']['id']]);
}

session_destroy();
header('Location: ../index.php');
exit;
