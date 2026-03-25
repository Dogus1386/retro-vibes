<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo 'Acceso denegado.';
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$newStatus = $_GET['status'] ?? '';

if ($id <= 0 || !in_array($newStatus, ['activo', 'bloqueado'])) {
    header('Location: admin.php');
    exit;
}

/* Evita que te bloquees a ti mismo */
if ($id === (int) $_SESSION['user_id'] && $newStatus !== 'activo') {
    header('Location: admin.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
$stmt->execute([$newStatus, $id]);

header('Location: admin.php');
exit;