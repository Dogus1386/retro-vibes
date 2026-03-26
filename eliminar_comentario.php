<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

/* ======================
   VALIDAR CSRF
====================== */
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    die('Solicitud no válida');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$comentarioId = (int)($_POST['comentario_id'] ?? 0);

if ($userId <= 0 || $comentarioId <= 0) {
    header('Location: perfil.php?msg=error_eliminar');
    exit;
}

/* ======================
   VERIFICAR QUE EL COMENTARIO
   PERTENEZCA AL USUARIO LOGUEADO
====================== */
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

/* ======================
   ELIMINAR COMENTARIO
====================== */
$stmtEliminar = $pdo->prepare("
    DELETE FROM comments
    WHERE id = ? AND usuario_id = ?
");
$stmtEliminar->execute([$comentarioId, $userId]);

if ($stmtEliminar->rowCount() > 0) {
    header('Location: perfil.php?msg=comentario_eliminado');
    exit;
}

header('Location: perfil.php?msg=error_eliminar');
exit;
?>