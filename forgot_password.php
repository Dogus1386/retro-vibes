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





// ====== CONTENIDO DEL CORREO EN HTML ======
$asunto = 'Recuperación de contraseña - Retro Vibes';

$cuerpo = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de contraseña</title>
</head>
<body style="margin:0; padding:0; background:#f4f4f4; font-family:Arial, Helvetica, sans-serif; color:#222;">
    <div style="max-width:600px; margin:30px auto; background:#ffffff; border:1px solid #ddd; border-radius:10px; overflow:hidden;">
        
        <div style="background:#111; color:#00ffd5; padding:20px; text-align:center;">
            <h1 style="margin:0; font-size:28px;">Retro Vibes</h1>
            <p style="margin:8px 0 0 0; color:#ffffff; font-size:14px;">Recuperación de contraseña</p>
        </div>

        <div style="padding:30px;">
            <p style="font-size:16px; margin-top:0;">Hola ' . htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') . ',</p>

            <p style="font-size:16px; line-height:1.6;">
                Recibimos una solicitud para restablecer tu contraseña en <strong>Retro Vibes</strong>.
            </p>

            <p style="font-size:16px; line-height:1.6;">
                Haz clic en el siguiente botón para crear una nueva contraseña:
            </p>

            <p style="text-align:center; margin:30px 0;">
                <a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '" 
                   style="display:inline-block; background:#00c853; color:#ffffff; text-decoration:none; padding:14px 24px; border-radius:8px; font-size:16px; font-weight:bold;">
                   Restablecer contraseña
                </a>
            </p>

            <p style="font-size:14px; color:#555; line-height:1.6;">
                Este enlace expira en <strong>1 hora</strong>.
            </p>

            <p style="font-size:14px; color:#555; line-height:1.6;">
                Si el botón no funciona, copia y pega este enlace en tu navegador:
            </p>

            <p style="font-size:14px; word-break:break-all;">
                <a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '</a>
            </p>

            <hr style="border:none; border-top:1px solid #ddd; margin:30px 0;">

            <p style="font-size:14px; color:#777; line-height:1.6; margin-bottom:0;">
                Si no solicitaste este cambio, puedes ignorar este mensaje.
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