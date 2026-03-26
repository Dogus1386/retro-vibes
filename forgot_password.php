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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ====== VALIDAR CSRF ======
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    $email = trim($_POST['email'] ?? '');

    // ====== VALIDACIONES ======
    if ($email === '') {
        $mensaje = 'Debes ingresar tu correo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Correo no válido.';
    } else {

        // ====== MENSAJE GENERICO POR SEGURIDAD ======
        $mensaje = 'Si el correo existe y está activo, se ha enviado un enlace de recuperación.';

        try {
            $stmt = $pdo->prepare("SELECT id, nombre, email, status FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && $usuario['status'] === 'activo') {

                // ====== INVALIDAR TOKENS ANTERIORES SIN USAR ======
                $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = ? AND usado = 0");
                $stmt->execute([$usuario['id']]);

                // ====== GENERAR TOKEN ======
                $tokenPlano = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $tokenPlano);
                $expiraEn = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (usuario_id, token, expira_en, usado)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$usuario['id'], $tokenHash, $expiraEn]);

                // ====== CREAR ENLACE ======
                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $rutaBase = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $enlace = $protocolo . '://' . $host . $rutaBase . '/reset_password.php?token=' . urlencode($tokenPlano);

                // ====== CONTENIDO DEL CORREO ======
                $asunto = 'Recuperación de contraseña - Retro Vibes';

                $cuerpo = "Hola " . $usuario['nombre'] . ",\n\n";
                $cuerpo .= "Recibimos una solicitud para restablecer tu contraseña en Retro Vibes.\n\n";
                $cuerpo .= "Haz clic o copia este enlace en tu navegador:\n";
                $cuerpo .= $enlace . "\n\n";
                $cuerpo .= "Este enlace expira en 1 hora.\n\n";
                $cuerpo .= "Si no solicitaste este cambio, puedes ignorar este mensaje.\n\n";
                $cuerpo .= "Saludos,\nRetro Vibes";

                $headers = "From: no-reply@" . $host . "\r\n";
                $headers .= "Reply-To: no-reply@" . $host . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                // ====== ENVIAR CORREO ======
                @mail($usuario['email'], $asunto, $cuerpo, $headers);
            }

        } catch (PDOException $e) {
            $mensaje = 'Ocurrió un error al procesar la solicitud.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña | Retro Vibes</title>
</head>
<body>

    <h1>Recuperar contraseña</h1>

    <?php if ($mensaje): ?>
        <p><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>Correo:</label><br>
        <input type="email" name="email" maxlength="100" required><br><br>

        <button type="submit">Enviar enlace de recuperación</button>
    </form>

    <p><a href="login.php">Volver al login</a></p>

</body>
</html>