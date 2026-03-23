<?php
session_start();
require 'db.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $mensaje = 'Todos los campos son obligatorios.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_nombre'] = $usuario['nombre'];

            header('Location: index.php');
            exit;
        } else {
            $mensaje = 'Correo o contraseña incorrectos.';
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
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Correo:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Entrar</button>
    </form>

    <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
</body>
</html>