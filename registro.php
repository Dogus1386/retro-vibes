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

    // ====== VALIDACIONES ======
    if ($nombre === '' || $email === '' || $password === '') {
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
    } else {

        try {
            // ====== VERIFICAR SI EL CORREO YA EXISTE ======
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $mensaje = 'Ese correo ya está registrado.';
            } else {
                // ====== CREAR USUARIO ======
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $email, $passwordHash]);

                // ====== MENSAJE FLASH Y REDIRECCION ======
                $_SESSION['registro_exitoso'] = 'Usuario registrado correctamente. Ya puedes iniciar sesión.';
                header("Location: login.php");
                exit;
            }

        } catch (PDOException $e) {
            $mensaje = 'Ocurrió un error al registrar el usuario.';
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

        <button type="submit">Registrarme</button>
    </form>

</body>
</html>