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

// ====== MENSAJE FLASH DE REGISTRO EXITOSO ======
$mensaje = '';

if (!empty($_SESSION['registro_exitoso'])) {
    $mensaje = $_SESSION['registro_exitoso'];
    unset($_SESSION['registro_exitoso']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ====== VALIDAR CSRF ======
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ====== VALIDACIONES ======
    if ($email === '' || $password === '') {
        $mensaje = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Correo no válido.';
    } else {

        // ====== CONSULTA SEGURA ======
        $stmt = $pdo->prepare("SELECT id, nombre, email, password, role, status FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {

            if ($usuario['status'] === 'bloqueado') {
                $mensaje = 'Tu cuenta ha sido bloqueada.';
                sleep(1);

            } else {

                // ====== REGENERAR SESION ======
                session_regenerate_id(true);

                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_nombre'] = $usuario['nombre'];
                $_SESSION['user_email'] = $usuario['email'];
                $_SESSION['role'] = $usuario['role'];
                $_SESSION['status'] = $usuario['status'];

                header('Location: index.php');
                exit;
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
    <title>Login | Retro Vibes</title>
</head>
<body>

<h1>Iniciar sesión</h1>

<?php if ($mensaje): ?>
    <p><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="POST" action="">
    
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

    <label>Correo:</label><br>
    <input type="email" name="email" maxlength="100" required><br><br>

    <label>Contraseña:</label><br>
    <input type="password" name="password" maxlength="100" required><br><br>

    <button type="submit">Entrar</button>

</form>

<p><a href="forgot_password.php">¿Olvidaste tu contraseña?</a></p>
<p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>

</body>
</html>