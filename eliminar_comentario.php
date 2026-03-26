<?php
session_start();
require 'db.php';
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$comentarioId = intval($_POST['comentario_id'] ?? 0);

if ($userId <= 0 || $comentarioId <= 0) {
    header('Location: perfil.php?msg=error_eliminar');
    exit;
}

/* Verificar que el comentario pertenezca al usuario logueado */
$stmtVerificar = $pdo->prepare("
    SELECT id 
    FROM comments 
    WHERE id = ? AND usuario_id = ?
");
$stmtVerificar->execute([$comentarioId, $userId]);
$comentario = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

if (!$comentario) {
    header('Location: perfil.php?msg=no_autorizado');
    exit;
}

/* Eliminar comentario */
$stmtEliminar = $pdo->prepare("DELETE FROM comments WHERE id = ? AND usuario_id = ?");
$stmtEliminar->execute([$comentarioId, $userId]);

if ($stmtEliminar->rowCount() > 0) {
    header('Location: perfil.php?msg=comentario_eliminado');
    exit;
} else {
    header('Location: perfil.php?msg=error_eliminar');
    exit;
}
?>