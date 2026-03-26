<?php

// ====== HEADERS DE SEGURIDAD ======
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ====== CONFIGURAR SESION SEGURA ======
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require 'db.php';

$tokenPlano = trim($_GET['token'] ?? '');

if ($tokenPlano === '') {
    die('Token no válido.');
}

$tokenHash = hash('sha256', $tokenPlano);

try {
    $stmt = $pdo->prepare("
        SELECT ev.id, ev.usuario_id, ev.expira_en, ev.usado, u.status
        FROM email_verifications ev
        INNER JOIN usuarios u ON u.id = ev.usuario_id
        WHERE ev.token = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $verificacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verificacion) {
        die('Enlace no válido.');
    }

    if ((int)$verificacion['usado'] === 1) {
        die('Este enlace ya fue utilizado.');
    }

    if (strtotime($verificacion['expira_en']) < time()) {
        die('El enlace ha expirado.');
    }

    if ($verificacion['status'] === 'activo') {
        $_SESSION['verificacion_exitosa'] = 'Tu cuenta ya estaba verificada. Ya puedes iniciar sesión.';
        header('Location: login.php');
        exit;
    }

    if ($verificacion['status'] === 'bloqueado') {
        die('Tu cuenta está bloqueada.');
    }

    // ====== ACTIVAR USUARIO ======
    $stmt = $pdo->prepare("UPDATE usuarios SET status = 'activo' WHERE id = ?");
    $stmt->execute([$verificacion['usuario_id']]);

    // ====== MARCAR TOKEN COMO USADO ======
    $stmt = $pdo->prepare("UPDATE email_verifications SET usado = 1 WHERE id = ?");
    $stmt->execute([$verificacion['id']]);

    // ====== INVALIDAR OTROS TOKENS DEL USUARIO ======
    $stmt = $pdo->prepare("UPDATE email_verifications SET usado = 1 WHERE usuario_id = ?");
    $stmt->execute([$verificacion['usuario_id']]);

    $_SESSION['verificacion_exitosa'] = 'Correo verificado correctamente. Ya puedes iniciar sesión.';
    header('Location: login.php');
    exit;

} catch (PDOException $e) {
    die('Ocurrió un error al verificar el correo.');
}
?>