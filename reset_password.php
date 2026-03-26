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

// ====== TOKEN CSRF ======
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = '';
$tokenPlano = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($tokenPlano === '') {
    die('Token no válido.');
}

$tokenHash = hash('sha256', $tokenPlano);

try {
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.usuario_id, pr.expira_en, pr.usado, u.status
        FROM password_resets pr
        INNER JOIN usuarios u ON u.id = pr.usuario_id
        WHERE pr.token = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        die('Enlace no válido.');
    }

    if ((int)$reset['usado'] === 1) {
        die('Este enlace ya fue utilizado.');
    }

    if ($reset['status'] !== 'activo') {
        die('La cuenta no está activa.');
    }

    if (strtotime($reset['expira_en']) < time()) {
        die('El enlace ha expirado.');
    }

} catch (PDOException $e) {
    die('Ocurrió un error al validar el enlace.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ====== VALIDAR CSRF ======
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    $password = trim($_POST['password'] ?? '');
    $confirmar_password = trim($_POST['confirmar_password'] ?? '');

    // ====== VALIDACIONES ======
    if ($password === '' || $confirmar_password === '') {
        $mensaje = 'Todos los campos son obligatorios.';
    } elseif (strlen($password) < 6 || strlen($password) > 100) {
        $mensaje = 'La contraseña debe tener entre 6 y 100 caracteres.';
    } elseif ($password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden.';
    } else {

        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // ====== ACTUALIZAR CONTRASEÑA ======
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $reset['usuario_id']]);

            // ====== MARCAR TOKEN COMO USADO ======
            $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE id = ?");
            $stmt->execute([$reset['id']]);

            // ====== INVALIDAR CUALQUIER OTRO TOKEN DEL USUARIO ======
            $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = ?");
            $stmt->execute([$reset['usuario_id']]);

            $_SESSION['registro_exitoso'] = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
            header("Location: login.php");
            exit;

        } catch (PDOException $e) {
            $mensaje = 'Ocurrió un error al actualizar la contraseña.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva contraseña | Retro Vibes</title>
</head>
<body>

    <h1>Crear nueva contraseña</h1>

    <?php if ($mensaje): ?>
        <p><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenPlano, ENT_QUOTES, 'UTF-8'); ?>">

        <label>Nueva contraseña:</label><br>
        <input type="password" name="password" minlength="6" maxlength="100" required><br><br>

        <label>Confirmar nueva contraseña:</label><br>
        <input type="password" name="confirmar_password" minlength="6" maxlength="100" required><br><br>

        <button type="submit">Guardar nueva contraseña</button>
    </form>

</body>
</html>