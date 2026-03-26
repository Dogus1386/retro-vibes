<?php
session_start();
require 'db.php';
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$usuarioId = $_SESSION['user_id'] ?? 0;
$commentId = intval($_POST['comment_id'] ?? 0);
$redirect = $_POST['redirect'] ?? 'index.php';

if ($usuarioId <= 0 || $commentId <= 0) {
    header("Location: $redirect");
    exit;
}

/* Verificar si el comentario existe */
$stmtComentario = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
$stmtComentario->execute([$commentId]);
$comentarioExiste = $stmtComentario->fetch(PDO::FETCH_ASSOC);

if (!$comentarioExiste) {
    header("Location: $redirect");
    exit;
}

/* Verificar si ya tiene like */
$stmtLike = $pdo->prepare("
    SELECT id 
    FROM comment_likes 
    WHERE comment_id = ? AND usuario_id = ?
");
$stmtLike->execute([$commentId, $usuarioId]);
$likeExiste = $stmtLike->fetch(PDO::FETCH_ASSOC);

if ($likeExiste) {
    /* quitar like */
    $stmtDelete = $pdo->prepare("
        DELETE FROM comment_likes 
        WHERE comment_id = ? AND usuario_id = ?
    ");
    $stmtDelete->execute([$commentId, $usuarioId]);
} else {
    /* poner like */
    $stmtInsert = $pdo->prepare("
        INSERT INTO comment_likes (comment_id, usuario_id) 
        VALUES (?, ?)
    ");
    $stmtInsert->execute([$commentId, $usuarioId]);
}

header("Location: $redirect");
exit;
?>