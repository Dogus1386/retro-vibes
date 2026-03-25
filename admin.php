<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    echo "Acceso denegado. Esta área es solo para administradores.";
    exit;
}

$stmt = $pdo->query("
    SELECT id, post_slug, author_name, comment_text, created_at 
    FROM comments 
    ORDER BY id DESC
");
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin | Retro Vibes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #111;
            color: #fff;
            padding: 20px;
        }

        h1 {
            color: #ff9800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #1b1b1b;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #222;
            color: #ff9800;
        }

        a.btn {
            display: inline-block;
            padding: 8px 12px;
            background: crimson;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        a.btn:hover {
            background: darkred;
        }

        .top-links {
            margin-bottom: 20px;
        }

        .top-links a {
            color: #ff9800;
            margin-right: 15px;
            text-decoration: none;
        }

        .top-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <h1>Panel de Administración - Retro Vibes</h1>

    <div class="top-links">
        <a href="index.php">Ir al inicio</a>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <h2>Listado de comentarios</h2>

    <?php if (count($comments) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Post</th>
                <th>Autor</th>
                <th>Comentario</th>
                <th>Fecha</th>
                <th>Acción</th>
            </tr>

            <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($comment["id"]); ?></td>
                    <td><?php echo htmlspecialchars($comment["post_slug"]); ?></td>
                    <td><?php echo htmlspecialchars($comment["author_name"]); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($comment["comment_text"])); ?></td>
                    <td><?php echo htmlspecialchars($comment["created_at"]); ?></td>
                    <td>
                        <a class="btn" href="delete-comment.php?id=<?php echo $comment["id"]; ?>" onclick="return confirm('¿Seguro que deseas eliminar este comentario?');">
                            Eliminar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No hay comentarios registrados.</p>
    <?php endif; ?>

</body>
</html>