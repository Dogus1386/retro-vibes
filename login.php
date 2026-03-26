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

// ====== CONFIGURACION DE SEGURIDAD ======
$maxIntentos = 5;
$minutosBloqueo = 15;

// ====== MENSAJE FLASH ======
$mensaje = '';

if (!empty($_SESSION['registro_exitoso'])) {
    $mensaje = $_SESSION['registro_exitoso'];
    unset($_SESSION['registro_exitoso']);
}

if (!empty($_SESSION['verificacion_exitosa'])) {
    $mensaje = $_SESSION['verificacion_exitosa'];
    unset($_SESSION['verificacion_exitosa']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $mensaje = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Correo no válido.';
    } else {

        $stmt = $pdo->prepare("
            SELECT id, nombre, email, password, role, status, failed_login_attempts, lock_until
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {

            // ====== VALIDAR BLOQUEO TEMPORAL ======
            if (!empty($usuario['lock_until']) && strtotime($usuario['lock_until']) > time()) {
                $segundosRestantes = strtotime($usuario['lock_until']) - time();
                $minutosRestantes = ceil($segundosRestantes / 60);
                $mensaje = 'Demasiados intentos fallidos. Intenta nuevamente en ' . $minutosRestantes . ' minuto(s).';
            } else {

                // ====== SI YA VENCIO EL BLOQUEO, LIMPIAR ======
                if (!empty($usuario['lock_until']) && strtotime($usuario['lock_until']) <= time()) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET failed_login_attempts = 0, lock_until = NULL WHERE id = ?");
                    $stmt->execute([$usuario['id']]);
                    $usuario['failed_login_attempts'] = 0;
                    $usuario['lock_until'] = null;
                }

                if (password_verify($password, $usuario['password'])) {

                    if ($usuario['status'] === 'bloqueado') {
                        $mensaje = 'Tu cuenta ha sido bloqueada.';
                        sleep(1);

                    } elseif ($usuario['status'] === 'pendiente') {
                        $mensaje = 'Debes verificar tu correo antes de iniciar sesión. Si no encuentras el correo, revisa spam o correo no deseado.';
                        sleep(1);

                    } elseif ($usuario['status'] === 'activo') {

                        // ====== LIMPIAR INTENTOS FALLIDOS ======
                        $stmt = $pdo->prepare("UPDATE usuarios SET failed_login_attempts = 0, lock_until = NULL WHERE id = ?");
                        $stmt->execute([$usuario['id']]);

                        // ====== REGENERAR SESION ======
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $usuario['id'];
                        $_SESSION['user_nombre'] = $usuario['nombre'];
                        $_SESSION['user_email'] = $usuario['email'];
                        $_SESSION['role'] = $usuario['role'];
                        $_SESSION['status'] = $usuario['status'];

                        header('Location: index.php');
                        exit;
                    } else {
                        $mensaje = 'Tu cuenta no está disponible para iniciar sesión.';
                        sleep(1);
                    }

                } else {

                    // ====== SUMAR INTENTO FALLIDO ======
                    $intentos = (int)$usuario['failed_login_attempts'] + 1;

                    if ($intentos >= $maxIntentos) {
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+' . $minutosBloqueo . ' minutes'));

                        $stmt = $pdo->prepare("
                            UPDATE usuarios
                            SET failed_login_attempts = 0, lock_until = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$lockUntil, $usuario['id']]);

                        $mensaje = 'Demasiados intentos fallidos. Tu acceso ha sido bloqueado temporalmente por ' . $minutosBloqueo . ' minutos.';
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios
                            SET failed_login_attempts = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$intentos, $usuario['id']]);

                        $restantes = $maxIntentos - $intentos;
                        $mensaje = 'Correo o contraseña incorrectos. Intentos restantes antes del bloqueo: ' . $restantes . '.';
                    }

                    sleep(1);
                }
            }

        } else {
            $mensaje = 'Correo o contraseña incorrectos.';
            sleep(1);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Retro Vibes</title>
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

        .login-wrap {
            width: 100%;
            max-width: 1000px;
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
            .login-wrap {
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

    <div class="login-wrap">
        <div class="hero">
            <div class="brand">
                <h1>Retro Vibes</h1>
                <p>Entra a tu cuenta y sigue explorando el mundo retro. Publica comentarios, da likes y forma parte de la comunidad gamer clásica.</p>
            </div>

            <div class="features">
                <div class="feature">🎮 Comunidad retro activa</div>
                <div class="feature">💬 Comentarios, likes y perfil personal</div>
                <div class="feature">🔐 Acceso seguro con verificación y recuperación</div>
            </div>
        </div>

        <div class="panel">
            <h2>Iniciar sesión</h2>
            <p class="sub">Ingresa tus credenciales para acceder a tu cuenta.</p>

            <?php if ($mensaje): ?>
                <div class="mensaje"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="campo">
                    <label for="email">Correo</label>
                    <input type="email" id="email" name="email" maxlength="100" required>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" maxlength="100" required>
                </div>

                <button type="submit">Entrar</button>
            </form>

            <div class="links">
                <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
                <a href="resend_verification.php">Reenviar correo de verificación</a>
                <a href="registro.php">¿No tienes cuenta? Regístrate aquí</a>
            </div>

            <div class="hint">
                Si registraste tu cuenta y no ves el correo de verificación, revisa también la carpeta de spam o correo no deseado.
            </div>
        </div>
    </div>

</body>
</html>