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
        $stmt = $pdo->prepare("SELECT id, nombre, email, password, role, status FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {

            if ($usuario['status'] === 'bloqueado') {
                $mensaje = 'Tu cuenta ha sido bloqueada.';

            } else {
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
        <p><?php echo htmlspecialchars($mensaje); ?></p>
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