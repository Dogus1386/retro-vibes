<?php
require_once 'security.php';
require_once 'db.php';

$usuarioLogueado = false;
$user = null;

/* ======================
   VALIDAR SESION SI EXISTE
====================== */
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, nombre, email, role, status FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['status'] !== 'bloqueado') {
        $usuarioLogueado = true;

        /* ======================
           REFRESCAR SESION
        ====================== */
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['status'] = $user['status'];

    } else {
        /* ======================
           SI USUARIO NO EXISTE O ESTA BLOQUEADO
           CERRAR SESION PERO NO REDIRIGIR
        ====================== */
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
    }
}
?>