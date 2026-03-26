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

$mensaje = '';
$estado = 'ok';

$tokenPlano = trim($_GET['token'] ?? '');

if ($tokenPlano === '') {
    $mensaje = 'Token no válido.';
    $estado = 'error';
} else {
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
            $mensaje = 'Enlace no válido.';
            $estado = 'error';
        } elseif ((int)$verificacion['usado'] === 1) {
            $mensaje = 'Este enlace ya fue utilizado.';
            $estado = 'error';
        } elseif (strtotime($verificacion['expira_en']) < time()) {
            $mensaje = 'El enlace ha expirado.';
            $estado = 'error';
        } elseif ($verificacion['status'] === 'activo') {
            $mensaje = 'Tu cuenta ya estaba verificada. Ya puedes iniciar sesión.';
            $estado = 'ok';
        } elseif ($verificacion['status'] === 'bloqueado') {
            $mensaje = 'Tu cuenta está bloqueada.';
            $estado = 'error';
        } else {
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
            $mensaje = 'Correo verificado correctamente. Tu cuenta ya está activa y puedes iniciar sesión.';
            $estado = 'ok';
        }

    } catch (PDOException $e) {
        $mensaje = 'Ocurrió un error al verificar el correo.';
        $estado = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar correo | Retro Vibes</title>
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
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.7;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .mensaje.ok {
            background: rgba(0, 200, 83, 0.14);
            color: #e8fff1;
        }

        .mensaje.error {
            background: rgba(213, 0, 0, 0.14);
            color: #ffecec;
        }

        .btn {
            display: inline-block;
            text-align: center;
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 15px;
            background: linear-gradient(90deg, #00c853, #00ffd5);
            color: #051018;
            font-weight: bold;
            font-size: 15px;
            text-decoration: none;
            margin-top: 4px;
        }

        .btn:hover {
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
                <p>Tu correo es la llave para activar la cuenta y comenzar oficialmente tu aventura dentro del universo retro.</p>
            </div>

            <div class="features">
                <div class="feature">📧 Verificación segura de correo</div>
                <div class="feature">✅ Activación de cuenta en un clic</div>
                <div class="feature">🎮 Acceso a comentarios, likes y comunidad</div>
            </div>
        </div>

        <div class="panel">
            <h2>Verificación de correo</h2>
            <p class="sub">Resultado del proceso de activación de tu cuenta.</p>

            <div class="mensaje <?php echo $estado === 'ok' ? 'ok' : 'error'; ?>">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <a class="btn" href="login.php">Ir al login</a>

            <div class="links">
                <a href="resend_verification.php">Reenviar correo de verificación</a>
                <a href="registro.php">Crear una nueva cuenta</a>
            </div>

            <div class="hint">
                Si el enlace expiró, puedes solicitar uno nuevo desde la opción de reenvío.
            </div>
        </div>
    </div>

</body>
</html>