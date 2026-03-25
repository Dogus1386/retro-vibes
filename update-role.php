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
$newRole = $_GET['role'] ?? '';

if ($id <= 0 || !in_array($newRole, ['admin', 'user'])) {
    header('Location: admin.php');
    exit;
}

/*
   Evitar que el admin se quite su propio rol por accidente.
*/
if ($id === (int) $_SESSION['user_id'] && $newRole !== 'admin') {
    header('Location: admin.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE usuarios SET role = ? WHERE id = ?");
$stmt->execute([$newRole, $id]);

header('Location: admin.php');
exit;