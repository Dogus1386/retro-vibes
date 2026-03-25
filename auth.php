<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

if (isset($_SESSION['user_id'])) {

    $stmt = $pdo->prepare("SELECT status FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['status'] === 'bloqueado') {

        session_unset();
        session_destroy();

        header("Location: login.php?blocked=1");
        exit;
    }
}