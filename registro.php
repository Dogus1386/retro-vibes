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

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmar_password = trim($_POST['confirmar_password'] ?? '');

    // ====== VALIDACIONES ======
    if ($nombre === '' || $email === '' || $password === '' || $confirmar_password === '') {
        $mensaje = 'Todos los campos son obligatorios.';
    } elseif (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 50) {
        $mensaje = 'El nombre debe tener entre 3 y 50 caracteres.';
    } elseif (!preg_match('/^[\p{L}\p{N} ]+$/u', $nombre)) {
        $mensaje = 'El nombre solo puede contener letras, números y espacios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Correo no válido.';
    } elseif (mb_strlen($email) > 100) {
        $mensaje = 'El correo es demasiado largo.';
    } elseif (strlen($password) < 6 || strlen($password) > 100) {
        $mensaje = 'La contraseña debe tener entre 6 y 100 caracteres.';
    } elseif ($password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden.';
    } else {

        try {
            // ====== VERIFICAR SI EL CORREO YA EXISTE ======
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $mensaje = 'Ese correo ya está registrado.';
            } else {
                // ====== CREAR USUARIO PENDIENTE ======
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nombre, email, password, role, status, creado_en)
                    VALUES (?, ?, ?, 'user', 'pendiente', NOW())
                ");
                $stmt->execute([$nombre, $email, $passwordHash]);

                $usuarioId = (int)$pdo->lastInsertId();

                // ====== INVALIDAR TOKENS ANTERIORES ======
                $stmt = $pdo->prepare("UPDATE email_verifications SET usado = 1 WHERE usuario_id = ?");
                $stmt->execute([$usuarioId]);

                // ====== GENERAR TOKEN ======
                $tokenPlano = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $tokenPlano);
                $expiraEn = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $stmt = $pdo->prepare("
                    INSERT INTO email_verifications (usuario_id, token, expira_en, usado)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$usuarioId, $tokenHash, $expiraEn]);

                // ====== CREAR ENLACE ======
                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $rutaBase = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $enlace = $protocolo . '://' . $host . $rutaBase . '/verify_email.php?token=' . urlencode($tokenPlano);

                // ====== CORREO HTML ======
                $asunto = 'Verifica tu correo - Retro Vibes';

                $cuerpo = '
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <title>Verificación de correo</title>
                </head>
                <body style="margin:0; padding:0; background:#f4f4f4; font-family:Arial, Helvetica, sans-serif; color:#222;">
                    <div style="max-width:600px; margin:30px auto; background:#ffffff; border:1px solid #ddd; border-radius:10px; overflow:hidden;">

                        <div style="background:#111; color:#00ffd5; padding:20px; text-align:center;">
                            <h1 style="margin:0; font-size:28px;">Retro Vibes</h1>
                            <p style="margin:8px 0 0 0; color:#ffffff; font-size:14px;">Verificación de correo</p>
                        </div>

                        <div style="padding:30px;">
                            <p style="font-size:16px; margin-top:0;">Hola ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . ',</p>

                            <p style="font-size:16px; line-height:1.6;">
                                Gracias por registrarte en <strong>Retro Vibes</strong>.
                            </p>

                            <p style="font-size:16px; line-height:1.6;">
                                Para activar tu cuenta, haz clic en el siguiente botón:
                            </p>

                            <p style="text-align:center; margin:30px 0;">
                                <a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '"
                                   style="display:inline-block; background:#00c853; color:#ffffff; text-decoration:none; padding:14px 24px; border-radius:8px; font-size:16px; font-weight:bold;">
                                   Verificar correo
                                </a>
                            </p>

                            <p style="font-size:14px; color:#555; line-height:1.6;">
                                Este enlace expira en <strong>24 horas</strong>.
                            </p>

                            <p style="font-size:14px; color:#555; line-height:1.6;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>

                            <p style="font-size:14px; word-break:break-all;">
                                <a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '</a>
                            </p>

                            <hr style="border:none; border-top:1px solid #ddd; margin:30px 0;">

                            <p style="font-size:14px; color:#777; line-height:1.6; margin-bottom:0;">
                                Si no realizaste este registro, puedes ignorar este mensaje.
                            </p>
                        </div>

                        <div style="background:#f8f8f8; padding:15px; text-align:center; font-size:12px; color:#777;">
                            © Retro Vibes
                        </div>
                    </div>
                </body>
                </html>
                ';

                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "From: Retro Vibes <no-reply@" . $host . ">\r\n";
                $headers .= "Reply-To: no-reply@" . $host . "\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

                @mail($email, $asunto, $cuerpo, $headers);

                $_SESSION['registro_exitoso'] = 'Registro exitoso. Revisa tu correo para verificar tu cuenta antes de iniciar sesión.';
                header("Location: login.php");
                exit;
            }

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = 'Ese correo ya está registrado.';
            } else {
                $mensaje = 'Ocurrió un error al registrar el usuario.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro | Retro Vibes</title>
</head>
<body>

    <h1>Registro de miembros</h1>

    <?php if ($mensaje): ?>
        <p><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>Nombre:</label><br>
        <input type="text" name="nombre" maxlength="50" required><br><br>

        <label>Correo:</label><br>
        <input type="email" name="email" maxlength="100" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" minlength="6" maxlength="100" required><br><br>

        <label>Confirmar contraseña:</label><br>
        <input type="password" name="confirmar_password" minlength="6" maxlength="100" required><br><br>

        <button type="submit">Registrarme</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>

</body>
</html>