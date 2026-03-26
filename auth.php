<?php
require_once 'security.php';
require_once 'db.php';

/* ======================
   SI NO HAY SESION, REDIRIGIR
====================== */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ======================
   VALIDAR USUARIO EN BD
====================== */
$stmt = $pdo->prepare("SELECT id, nombre, email, role, status FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] === 'bloqueado') {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: login.php?blocked=1");
    exit;
}

/* ======================
   REFRESCAR SESION
====================== */
$_SESSION['user_nombre'] = $user['nombre'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['status'] = $user['status'];