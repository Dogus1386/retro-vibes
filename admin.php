<?php
require_once 'auth.php';
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo 'Acceso denegado. Esta área es solo para administradores.';
    exit;
}

/* =========================
   COMENTARIOS
========================= */
$stmtComments = $pdo->query("
    SELECT id, post_slug, author_name, comment_text, created_at, status
    FROM comments
    ORDER BY id DESC
");
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   USUARIOS
========================= */
$stmtUsers = $pdo->query("
    SELECT id, nombre, email, role, status
    FROM usuarios
    ORDER BY id DESC
");
$usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin | Retro Vibes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f0f0f;
            color: #ffffff;
            padding: 20px;
        }

        h1 {
            color: #ff9800;
            margin-bottom: 10px;
        }

        h2 {
            color: #ffffff;
            margin-top: 40px;
            margin-bottom: 15px;
        }

        .top-links {
            margin-bottom: 20px;
        }

        .top-links a {
            color: #ff9800;
            text-decoration: none;
            margin-right: 20px;
            font-weight: bold;
        }

        .top-links a:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #1a1a1a;
            margin-bottom: 40px;
        }

        th, td {
            border: 1px solid #333;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #222;
            color: #ff9800;
        }

        tr:nth-child(even) {
            background: #151515;
        }

        .btn-delete {
            display: inline-block;
            padding: 8px 14px;
            background: crimson;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .btn-delete:hover {
            background: darkred;
        }

        .btn-role {
            display: inline-block;
            padding: 8px 14px;
            background: #2196f3;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-right: 8px;
            margin-bottom: 6px;
        }

        .btn-role:hover {
            background: #0b7dda;
        }

        .btn-status-block {
            display: inline-block;
            padding: 8px 14px;
            background: #ff5722;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .btn-status-block:hover {
            background: #e64a19;
        }

        .btn-status-active {
            display: inline-block;
            padding: 8px 14px;
            background: #4caf50;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .btn-status-active:hover {
            background: #388e3c;
        }

        .btn-comment-hide {
            display: inline-block;
            padding: 8px 14px;
            background: #ff9800;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-right: 8px;
            margin-bottom: 6px;
        }

        .btn-comment-hide:hover {
            background: #e68900;
        }

        .btn-comment-show {
            display: inline-block;
            padding: 8px 14px;
            background: #4caf50;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-right: 8px;
            margin-bottom: 6px;
        }

        .btn-comment-show:hover {
            background: #388e3c;
        }

        .badge-admin {
            display: inline-block;
            padding: 4px 10px;
            background: #4caf50;
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .badge-user {
            display: inline-block;
            padding: 4px 10px;
            background: #777;
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .badge-activo {
            display: inline-block;
            padding: 4px 10px;
            background: #00c853;
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .badge-bloqueado {
            display: inline-block;
            padding: 4px 10px;
            background: #d50000;
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .badge-visible {
            display: inline-block;
            padding: 4px 10px;
            background: #00c853;
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .badge-oculto {
            display: inline-block;
            padding: 4px 10px;
            background: #9e9e9e;
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .self-label {
            color: #ff9800;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Panel de Administración - Retro Vibes</h1>

    <div class="top-links">
        <a href="index.php">Ir al inicio</a>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <h2>Moderación de comentarios</h2>

    <?php if (count($comments) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Post</th>
                <th>Autor</th>
                <th>Comentario</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>

            <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($comment['id']); ?></td>
                    <td><?php echo htmlspecialchars($comment['post_slug']); ?></td>
                    <td><?php echo htmlspecialchars($comment['author_name']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></td>
                    <td><?php echo htmlspecialchars($comment['created_at']); ?></td>
                    <td>
                        <?php if (($comment['status'] ?? 'visible') === 'oculto'): ?>
                            <span class="badge-oculto">oculto</span>
                        <?php else: ?>
                            <span class="badge-visible">visible</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($comment['status'] ?? 'visible') === 'visible'): ?>
                            <a class="btn-comment-hide"
                               href="update-comment-status.php?id=<?php echo $comment['id']; ?>&status=oculto"
                               onclick="return confirm('¿Ocultar este comentario?');">
                               Ocultar
                            </a>
                        <?php else: ?>
                            <a class="btn-comment-show"
                               href="update-comment-status.php?id=<?php echo $comment['id']; ?>&status=visible"
                               onclick="return confirm('¿Mostrar este comentario nuevamente?');">
                               Mostrar
                            </a>
                        <?php endif; ?>

                        <br>

                        <a class="btn-delete"
                           href="delete-comment.php?id=<?php echo $comment['id']; ?>"
                           onclick="return confirm('¿Seguro que deseas eliminar este comentario?');">
                           Eliminar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No hay comentarios registrados.</p>
    <?php endif; ?>

    <h2>Gestión de usuarios</h2>

    <?php if (count($usuarios) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>

            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                        <?php if ((int)$usuario['id'] === (int)$_SESSION['user_id']): ?>
                            <span class="self-label">(Tú)</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td>
                        <?php if ($usuario['role'] === 'admin'): ?>
                            <span class="badge-admin">admin</span>
                        <?php else: ?>
                            <span class="badge-user">user</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($usuario['status'] === 'bloqueado'): ?>
                            <span class="badge-bloqueado">bloqueado</span>
                        <?php else: ?>
                            <span class="badge-activo">activo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$usuario['id'] !== (int)$_SESSION['user_id']): ?>

                            <?php if ($usuario['role'] === 'user'): ?>
                                <a class="btn-role"
                                   href="update-role.php?id=<?php echo $usuario['id']; ?>&role=admin"
                                   onclick="return confirm('¿Convertir este usuario en admin?');">
                                   Hacer admin
                                </a>
                            <?php else: ?>
                                <a class="btn-role"
                                   href="update-role.php?id=<?php echo $usuario['id']; ?>&role=user"
                                   onclick="return confirm('¿Quitar permisos de admin a este usuario?');">
                                   Hacer user
                                </a>
                            <?php endif; ?>

                            <br>

                            <?php if ($usuario['status'] === 'activo'): ?>
                                <a class="btn-status-block"
                                   href="update-status.php?id=<?php echo $usuario['id']; ?>&status=bloqueado"
                                   onclick="return confirm('¿Seguro que deseas bloquear este usuario?');">
                                   Bloquear
                                </a>
                            <?php else: ?>
                                <a class="btn-status-active"
                                   href="update-status.php?id=<?php echo $usuario['id']; ?>&status=activo"
                                   onclick="return confirm('¿Deseas activar nuevamente este usuario?');">
                                   Activar
                                </a>
                            <?php endif; ?>

                        <?php else: ?>
                            <span>No disponible</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No hay usuarios registrados.</p>
    <?php endif; ?>

</body>
</html>