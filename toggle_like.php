<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/* ======================
   VALIDAR CSRF
====================== */
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    die('Solicitud no válida');
}

$usuarioId = (int)($_SESSION['user_id'] ?? 0);
$commentId = (int)($_POST['comment_id'] ?? 0);
$redirect = trim($_POST['redirect'] ?? 'index.php');

/* ======================
   LISTA BLANCA DE REDIRECTS
====================== */
$redirectsPermitidos = [
    'index.php',
    'blog.php',
    'post-contra.php',
    'post-mario.php',
    'post-streetfighter.php'
];

if (!in_array($redirect, $redirectsPermitidos, true)) {
    $redirect = 'index.php';
}

if ($usuarioId <= 0 || $commentId <= 0) {
    header("Location: $redirect");
    exit;
}

/* ======================
   VERIFICAR SI EL COMENTARIO EXISTE
====================== */
$stmtComentario = $pdo->prepare("
    SELECT id
    FROM comments
    WHERE id = ? AND status = 'visible'
");
$stmtComentario->execute([$commentId]);
$comentarioExiste = $stmtComentario->fetch(PDO::FETCH_ASSOC);

if (!$comentarioExiste) {
    header("Location: $redirect");
    exit;
}

/* ======================
   VERIFICAR SI YA TIENE LIKE
====================== */
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