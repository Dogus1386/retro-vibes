<?php
session_start();
require 'db.php';
require 'auth.php';

$userId = $_SESSION['user_id'] ?? 0;
$comentarioId = intval($_GET['id'] ?? $_POST['comentario_id'] ?? 0);
$error = '';
$mensaje = '';

if ($userId <= 0 || $comentarioId <= 0) {
    header('Location: perfil.php?msg=no_autorizado');
    exit;
}

/* ======================
   OBTENER COMENTARIO DEL USUARIO
====================== */
$stmtComentario = $pdo->prepare("
    SELECT id, post_slug, comment_text, created_at, status
    FROM comments
    WHERE id = ? AND usuario_id = ?
");
$stmtComentario->execute([$comentarioId, $userId]);
$comentario = $stmtComentario->fetch(PDO::FETCH_ASSOC);

if (!$comentario) {
    header('Location: perfil.php?msg=no_autorizado');
    exit;
}

/* ======================
   PROCESAR EDICIÓN
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoTexto = trim($_POST['comment_text'] ?? '');

    if ($nuevoTexto === '') {
        $error = 'El comentario no puede ir vacío.';
    } elseif (mb_strlen($nuevoTexto) > 500) {
        $error = 'El comentario no puede superar los 500 caracteres.';
    } else {
        $stmtUpdate = $pdo->prepare("
            UPDATE comments
            SET comment_text = ?
            WHERE id = ? AND usuario_id = ?
        ");
        $stmtUpdate->execute([$nuevoTexto, $comentarioId, $userId]);

        header('Location: perfil.php?msg=comentario_editado');
        exit;
    }

    $comentario['comment_text'] = $nuevoTexto;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar comentario | Retro Vibes</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #12061f 0%, #1d0b2e 50%, #0f172a 100%);
            color: #f8fafc;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
        }

        .card {
            background: rgba(15, 23, 42, 0.90);
            border: 2px solid #7c3aed;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.25);
        }

        .titulo {
            font-family: 'Press Start 2P', cursive;
            font-size: 20px;
            color: #facc15;
            text-align: center;
            margin-bottom: 18px;
            line-height: 1.7;
            text-shadow: 0 0 10px rgba(250, 204, 21, 0.35);
        }

        .subtitulo {
            text-align: center;
            color: #cbd5e1;
            margin-bottom: 28px;
            font-size: 15px;
            line-height: 1.6;
        }

        .info-box {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255,255,255,0.08);
            border-left: 5px solid #f97316;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 22px;
        }

        .info-box p {
            margin-bottom: 8px;
            color: #e2e8f0;
            font-size: 14px;
        }

        .info-box p:last-child {
            margin-bottom: 0;
        }

        .info-box strong {
            color: #facc15;
        }

        .estado-visible {
            display: inline-block;
            background: #14532d;
            color: #bbf7d0;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .estado-oculto {
            display: inline-block;
            background: #7f1d1d;
            color: #fecaca;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .form-textarea {
            width: 100%;
            min-height: 180px;
            padding: 16px;
            border-radius: 14px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #fff;
            font-size: 15px;
            outline: none;
            resize: vertical;
            line-height: 1.7;
        }

        .form-textarea:focus {
            border-color: #22d3ee;
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.15);
        }

        .ayuda {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 8px;
            line-height: 1.5;
        }

        .mensaje-error {
            background: rgba(127, 29, 29, 0.95);
            color: #fecaca;
            border: 1px solid #ef4444;
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .acciones {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 700;
            transition: 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-guardar {
            background: #06b6d4;
            color: #fff;
        }

        .btn-guardar:hover {
            background: #0891b2;
        }

        .btn-volver {
            background: #334155;
            color: #fff;
        }

        .btn-volver:hover {
            background: #1e293b;
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 12px;
            }

            .titulo {
                font-size: 15px;
            }

            .acciones {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="card">
            <h1 class="titulo">EDITAR COMENTARIO</h1>
            <p class="subtitulo">Modifica tu comentario y guarda los cambios.</p>

            <div class="info-box">
                <p><strong>Post:</strong> <?php echo htmlspecialchars($comentario['post_slug']); ?></p>
                <p><strong>Fecha:</strong> <?php echo htmlspecialchars($comentario['created_at']); ?></p>
                <p>
                    <strong>Estado:</strong>
                    <?php if ($comentario['status'] === 'visible'): ?>
                        <span class="estado-visible">Visible</span>
                    <?php else: ?>
                        <span class="estado-oculto">Oculto</span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="comentario_id" value="<?php echo (int)$comentario['id']; ?>">

                <div class="form-group">
                    <label for="comment_text">Tu comentario</label>
                    <textarea 
                        id="comment_text" 
                        name="comment_text" 
                        class="form-textarea"
                        maxlength="500"
                        required
                    ><?php echo htmlspecialchars($comentario['comment_text']); ?></textarea>

                    <div class="ayuda">
                        Máximo 500 caracteres.
                    </div>
                </div>

                <div class="acciones">
                    <button type="submit" class="btn btn-guardar">Guardar cambios</button>
                    <a href="perfil.php" class="btn btn-volver">Cancelar y volver</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>