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

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $mensaje = 'Debes ingresar tu correo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Correo no válido.';
    } else {
        $mensaje = 'Si el correo existe y la cuenta está pendiente de verificación, se ha reenviado el correo. Revisa tu bandeja de entrada, spam o correo no deseado.';

        try {
            $stmt = $pdo->prepare("SELECT id, nombre, email, status FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && $usuario['status'] === 'pendiente') {

                // ====== INVALIDAR TOKENS ANTERIORES ======
                $stmt = $pdo->prepare("UPDATE email_verifications SET usado = 1 WHERE usuario_id = ?");
                $stmt->execute([$usuario['id']]);

                // ====== GENERAR NUEVO TOKEN ======
                $tokenPlano = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $tokenPlano);
                $expiraEn = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $stmt = $pdo->prepare("
                    INSERT INTO email_verifications (usuario_id, token, expira_en, usado)
                    VALUES (?, ?, ?, 0)
                ");
                $stmt->execute([$usuario['id'], $tokenHash, $expiraEn]);

                // ====== CREAR ENLACE ======
                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $rutaBase = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $enlace = $protocolo . '://' . $host . $rutaBase . '/verify_email.php?token=' . urlencode($tokenPlano);

                // ====== CORREO HTML ======
                $asunto = 'Reenvío de verificación - Retro Vibes';

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
                            <p style="margin:8px 0 0 0; color:#ffffff; font-size:14px;">Reenvío de verificación</p>
                        </div>

                        <div style="padding:30px;">
                            <p style="font-size:16px; margin-top:0;">Hola ' . htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') . ',</p>

                            <p style="font-size:16px; line-height:1.6;">
                                Has solicitado un nuevo correo de verificación para tu cuenta de <strong>Retro Vibes</strong>.
                            </p>

                            <p style="font-size:16px; line-height:1.6;">
                                Haz clic en el siguiente botón para activar tu cuenta:
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
                                Si no ves este correo en tu bandeja principal, revisa también <strong>spam</strong> o <strong>correo no deseado</strong>.
                            </p>

                            <p style="font-size:14px; color:#555; line-height:1.6;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>

                            <p style="font-size:14px; word-break:break-all;">
                                <a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '</a>
                            </p>

                            <hr style="border:none; border-top:1px solid #ddd; margin:30px 0;">

                            <p style="font-size:14px; color:#777; line-height:1.6; margin-bottom:0;">
                                Si no solicitaste este reenvío, puedes ignorar este mensaje.
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
    <title>Reenviar verificación | Retro Vibes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top, rgba(0,255,213,0.15), transparent 30%),
                linear-gradient(180deg, #0b0f1a 0%, #111827 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: rgba(10, 15, 25, 0.95);
            border: 1px solid rgba(0,255,213,0.18);
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.45);
            padding: 32px;
        }

        h1 {
            margin: 0 0 10px 0;
            color: #00ffd5;
            text-align: center;
        }

        .sub {
            text-align: center;
            color: #b8c1cc;
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.5;
        }

        .mensaje {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            color: #f4f4f4;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #dbe4ee;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #2b3a4a;
            background: #0f172a;
            color: #fff;
            margin-bottom: 18px;
            outline: none;
        }

        input:focus {
            border-color: #00ffd5;
            box-shadow: 0 0 0 3px rgba(0,255,213,0.12);
        }

        button {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 14px;
            background: linear-gradient(90deg, #00c853, #00ffd5);
            color: #081018;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            opacity: 0.95;
        }

        .links {
            margin-top: 18px;
            text-align: center;
            font-size: 14px;
        }

        .links a {
            color: #00ffd5;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="card">
        <h1>Reenviar verificación</h1>
        <p class="sub">Ingresa tu correo para recibir un nuevo enlace de verificación. Revisa también spam o correo no deseado.</p>

        <?php if ($mensaje): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <label for="email">Correo</label>
            <input type="email" id="email" name="email" maxlength="100" required>

            <button type="submit">Reenviar correo</button>
        </form>

        <div class="links">
            <a href="login.php">Volver al login</a>
        </div>
    </div>

</body>
</html>