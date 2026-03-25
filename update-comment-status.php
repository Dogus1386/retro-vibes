<?php
require_once 'auth.php';
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo 'Acceso denegado.';
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$newStatus = $_GET['status'] ?? '';

if ($id <= 0 || !in_array($newStatus, ['visible', 'oculto'])) {
    header('Location: admin.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE comments SET status = ? WHERE id = ?");
$stmt->execute([$newStatus, $id]);

header('Location: admin.php');
exit;