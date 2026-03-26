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

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    $password = trim($_POST['password'] ?? '');
    $confirmar_password = trim($_POST['confirmar_password'] ?? '');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña | Retro Vibes</title>
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
                <p>Estás a un paso de volver. Crea una nueva contraseña segura y continúa tu viaje por el universo retro.</p>
            </div>

            <div class="features">
                <div class="feature">🔒 Cambio seguro de contraseña</div>
                <div class="feature">⚡ Enlace único y temporal</div>
                <div class="feature">🎮 Vuelve a tu cuenta en pocos pasos</div>
            </div>
        </div>

        <div class="panel">
            <h2>Nueva contraseña</h2>
            <p class="sub">Ingresa tu nueva contraseña para actualizar tu acceso.</p>

            <?php if ($mensaje): ?>
                <div class="mensaje"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenPlano, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="campo">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password" minlength="6" maxlength="100" required>
                </div>

                <div class="campo">
                    <label for="confirmar_password">Confirmar nueva contraseña</label>
                    <input type="password" id="confirmar_password" name="confirmar_password" minlength="6" maxlength="100" required>
                </div>

                <button type="submit">Guardar nueva contraseña</button>
            </form>

            <div class="links">
                <a href="login.php">Volver al login</a>
            </div>

            <div class="hint">
                Usa una contraseña que recuerdes bien y que no estés usando en otros sitios.
            </div>
        </div>
    </div>

</body>
</html>