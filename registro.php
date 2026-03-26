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

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmar_password = trim($_POST['confirmar_password'] ?? '');

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

                $_SESSION['registro_exitoso'] = 'Registro exitoso. Revisa tu correo para verificar tu cuenta antes de iniciar sesión. Revisa también spam o correo no deseado.';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Retro Vibes</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top, rgba(0,255,213,0.16), transparent 25%),
                radial-gradient(circle at bottom, rgba(255,152,0,0.12), transparent 25%),
                linear-gradient(180deg, #070b14 0%, #0f172a 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .wrap {
            width: 100%;
            max-width: 1040px;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: rgba(8, 12, 22, 0.92);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0,0,0,0.45);
        }

        .hero {
            padding: 48px;
            background:
                linear-gradient(180deg, rgba(0,0,0,0.15), rgba(0,0,0,0.35)),
                linear-gradient(135deg, #111827, #0b1020);
            position: relative;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(transparent 96%, rgba(255,255,255,0.05) 100%),
                linear-gradient(90deg, transparent 96%, rgba(255,255,255,0.05) 100%);
            background-size: 36px 36px;
            pointer-events: none;
        }

        .brand {
            position: relative;
            z-index: 1;
        }

        .brand h1 {
            margin: 0;
            font-size: 42px;
            color: #00ffd5;
            text-shadow: 0 0 14px rgba(0,255,213,0.3);
        }

        .brand p {
            color: #cdd6df;
            margin-top: 12px;
            line-height: 1.7;
            max-width: 420px;
        }

        .features {
            position: relative;
            z-index: 1;
            margin-top: 30px;
            display: grid;
            gap: 14px;
        }

        .feature {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 14px 16px;
            color: #dbe4ee;
        }

        .panel {
            padding: 42px 34px;
            background: rgba(6, 10, 18, 0.96);
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 8px;
            color: #fff;
            font-size: 30px;
        }

        .panel .sub {
            margin-top: 0;
            margin-bottom: 24px;
            color: #a8b3c2;
            font-size: 14px;
            line-height: 1.6;
        }

        .mensaje {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            color: #f5f7fa;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.6;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #dfe8f2;
        }

        .campo {
            margin-bottom: 18px;
        }

        input {
            width: 100%;
            padding: 14px 15px;
            border-radius: 12px;
            border: 1px solid #273449;
            background: #0f172a;
            color: #fff;
            outline: none;
            font-size: 14px;
        }

        input:focus {
            border-color: #00ffd5;
            box-shadow: 0 0 0 3px rgba(0,255,213,0.12);
        }

        button {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 15px;
            background: linear-gradient(90deg, #00c853, #00ffd5);
            color: #051018;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            margin-top: 4px;
        }

        button:hover {
            opacity: 0.96;
        }

        .links {
            margin-top: 18px;
            display: grid;
            gap: 10px;
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

        .hint {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #8894a5;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .wrap {
                grid-template-columns: 1fr;
            }

            .hero {
                padding: 30px 24px;
            }

            .brand h1 {
                font-size: 34px;
            }

            .panel {
                padding: 30px 22px;
            }
        }
    </style>
</head>
<body>

    <div class="wrap">
        <div class="hero">
            <div class="brand">
                <h1>Retro Vibes</h1>
                <p>Crea tu cuenta y forma parte de la comunidad retro. Comenta, da likes y guarda tu lugar en el mundo gamer clásico.</p>
            </div>

            <div class="features">
                <div class="feature">🕹️ Registro rápido y seguro</div>
                <div class="feature">📩 Verificación de correo para activar tu cuenta</div>
                <div class="feature">💬 Participa en comentarios y comunidad</div>
            </div>
        </div>

        <div class="panel">
            <h2>Crear cuenta</h2>
            <p class="sub">Completa tus datos para registrarte en Retro Vibes.</p>

            <?php if ($mensaje): ?>
                <div class="mensaje"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="campo">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="50" required>
                </div>

                <div class="campo">
                    <label for="email">Correo</label>
                    <input type="email" id="email" name="email" maxlength="100" required>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" minlength="6" maxlength="100" required>
                </div>

                <div class="campo">
                    <label for="confirmar_password">Confirmar contraseña</label>
                    <input type="password" id="confirmar_password" name="confirmar_password" minlength="6" maxlength="100" required>
                </div>

                <button type="submit">Registrarme</button>
            </form>

            <div class="links">
                <a href="login.php">¿Ya tienes cuenta? Inicia sesión aquí</a>
                <a href="resend_verification.php">Reenviar correo de verificación</a>
            </div>

            <div class="hint">
                Después de registrarte, deberás verificar tu correo para activar la cuenta. Si no ves el mensaje, revisa spam o correo no deseado.
            </div>
        </div>
    </div>

</body>
</html>