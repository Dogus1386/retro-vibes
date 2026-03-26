<?php
require_once 'auth.php';
require_once 'db.php';

$userId = $_SESSION['user_id'];
$mensajePerfil = '';
$mensajePassword = '';
$errorPerfil = '';
$errorPassword = '';
$mensajeComentario = '';
$tipoMensajeComentario = '';

/* ======================
   TOKEN CSRF
====================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ======================
   MENSAJES DE COMENTARIOS
====================== */
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'comentario_eliminado') {
        $mensajeComentario = 'Comentario eliminado correctamente.';
        $tipoMensajeComentario = 'ok';
    } elseif ($_GET['msg'] === 'error_eliminar') {
        $mensajeComentario = 'No se pudo eliminar el comentario.';
        $tipoMensajeComentario = 'error';
    } elseif ($_GET['msg'] === 'no_autorizado') {
        $mensajeComentario = 'No tienes permiso para realizar esa acción.';
        $tipoMensajeComentario = 'error';
    } elseif ($_GET['msg'] === 'comentario_editado') {
        $mensajeComentario = 'Comentario actualizado correctamente.';
        $tipoMensajeComentario = 'ok';
    } elseif ($_GET['msg'] === 'error_editar') {
        $mensajeComentario = 'No se pudo actualizar el comentario.';
        $tipoMensajeComentario = 'error';
    }
}

/* ======================
   PROCESAR ACTUALIZAR NOMBRE
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_nombre'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    $nuevoNombre = trim($_POST['nombre'] ?? '');

    if ($nuevoNombre === '') {
        $errorPerfil = 'El nombre no puede ir vacío.';
    } elseif (mb_strlen($nuevoNombre) < 3) {
        $errorPerfil = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (mb_strlen($nuevoNombre) > 50) {
        $errorPerfil = 'El nombre no puede superar los 50 caracteres.';
    } elseif (!preg_match('/^[\p{L}\p{N} ]+$/u', $nuevoNombre)) {
        $errorPerfil = 'El nombre solo puede contener letras, números y espacios.';
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $stmtUpdate->execute([$nuevoNombre, $userId]);

        /* actualizar nombre en comentarios */
        $stmtUpdateComentarios = $pdo->prepare("
            UPDATE comments 
            SET author_name = ?
            WHERE usuario_id = ?
        ");
        $stmtUpdateComentarios->execute([$nuevoNombre, $userId]);

        $_SESSION['user_nombre'] = $nuevoNombre;
        $mensajePerfil = 'Nombre actualizado correctamente.';
    }
}

/* ======================
   PROCESAR CAMBIO DE CONTRASEÑA
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    $passwordActual = $_POST['password_actual'] ?? '';
    $passwordNueva = $_POST['password_nueva'] ?? '';
    $passwordConfirmar = $_POST['password_confirmar'] ?? '';

    if ($passwordActual === '' || $passwordNueva === '' || $passwordConfirmar === '') {
        $errorPassword = 'Todos los campos de contraseña son obligatorios.';
    } elseif (strlen($passwordNueva) < 6) {
        $errorPassword = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif (strlen($passwordNueva) > 100) {
        $errorPassword = 'La nueva contraseña es demasiado larga.';
    } elseif ($passwordNueva !== $passwordConfirmar) {
        $errorPassword = 'La confirmación de la contraseña no coincide.';
    } elseif ($passwordActual === $passwordNueva) {
        $errorPassword = 'La nueva contraseña no puede ser igual a la actual.';
    } else {
        $stmtPassword = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmtPassword->execute([$userId]);
        $usuarioPassword = $stmtPassword->fetch(PDO::FETCH_ASSOC);

        if (!$usuarioPassword || !password_verify($passwordActual, $usuarioPassword['password'])) {
            $errorPassword = 'La contraseña actual no es correcta.';
        } else {
            $nuevoHash = password_hash($passwordNueva, PASSWORD_DEFAULT);

            $stmtUpdatePassword = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmtUpdatePassword->execute([$nuevoHash, $userId]);

            $mensajePassword = 'Contraseña actualizada correctamente.';
        }
    }
}

/* ======================
   OBTENER DATOS USUARIO
====================== */
$stmt = $pdo->prepare("SELECT id, nombre, email, role, creado_en 
                       FROM usuarios 
                       WHERE id = ?");
$stmt->execute([$userId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

/* ======================
   OBTENER COMENTARIOS
====================== */
$stmtComentarios = $pdo->prepare("
    SELECT id, post_slug, comment_text, created_at, status
    FROM comments
    WHERE usuario_id = ?
    ORDER BY created_at DESC
");
$stmtComentarios->execute([$userId]);
$comentarios = $stmtComentarios->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | Retro Vibes</title>

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
            max-width: 1250px;
            margin: 0 auto;
        }

        .titulo {
            font-family: 'Press Start 2P', cursive;
            font-size: 24px;
            color: #facc15;
            text-align: center;
            margin-bottom: 15px;
            line-height: 1.6;
            text-shadow: 0 0 10px rgba(250, 204, 21, 0.4);
        }

        .subtitulo {
            text-align: center;
            color: #cbd5e1;
            margin-bottom: 35px;
            font-size: 15px;
        }

        .grid-perfil {
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr;
            gap: 25px;
        }

        .card {
            background: rgba(15, 23, 42, 0.88);
            border: 2px solid #7c3aed;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 0 18px rgba(124, 58, 237, 0.25);
        }

        .card h2 {
            font-family: 'Press Start 2P', cursive;
            font-size: 14px;
            color: #22d3ee;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .perfil-dato {
            margin-bottom: 18px;
            padding: 14px;
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .perfil-dato span,
        .form-group label {
            display: block;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .perfil-dato strong {
            font-size: 15px;
            color: #ffffff;
            word-break: break-word;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid #334155;
            background: #1e293b;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: #22d3ee;
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.15);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
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
            width: 100%;
        }

        .btn-guardar:hover {
            background: #0891b2;
        }

        .btn-password {
            background: #8b5cf6;
            color: #fff;
            width: 100%;
        }

        .btn-password:hover {
            background: #7c3aed;
        }

        .btn-inicio {
            background: #f97316;
            color: white;
        }

        .btn-inicio:hover {
            background: #ea580c;
        }

        .btn-logout {
            background: #334155;
            color: white;
        }

        .btn-logout:hover {
            background: #1e293b;
        }

        .btn-eliminar {
            background: #dc2626;
            color: #fff;
            padding: 10px 14px;
            font-size: 13px;
        }

        .btn-eliminar:hover {
            background: #b91c1c;
        }

        .btn-editar {
            background: #2563eb;
            color: #fff;
            padding: 10px 14px;
            font-size: 13px;
            text-decoration: none;
        }

        .btn-editar:hover {
            background: #1d4ed8;
        }

        .acciones {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .mensaje-ok {
            background: rgba(20, 83, 45, 0.95);
            color: #bbf7d0;
            border: 1px solid #22c55e;
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
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

        .comentario {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255,255,255,0.08);
            border-left: 5px solid #f97316;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 18px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .comentario:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(249, 115, 22, 0.18);
        }

        .comentario-top {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .comentario-top div {
            font-size: 14px;
            color: #e2e8f0;
        }

        .comentario-top strong {
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

        .texto-comentario {
            color: #f8fafc;
            line-height: 1.7;
            font-size: 15px;
            margin-bottom: 14px;
            word-break: break-word;
        }

        .comentario-acciones {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .sin-comentarios {
            background: rgba(30, 41, 59, 0.95);
            border: 1px dashed #64748b;
            color: #cbd5e1;
            padding: 20px;
            border-radius: 14px;
            text-align: center;
        }

        .ayuda {
            font-size: 13px;
            color: #94a3b8;
            margin-top: -6px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        @media (max-width: 1100px) {
            .grid-perfil {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 25px 12px;
            }

            .titulo {
                font-size: 16px;
            }

            .subtitulo {
                font-size: 14px;
            }

            .card h2 {
                font-size: 12px;
            }

            .comentario-acciones {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-editar,
            .btn-eliminar {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1 class="titulo">MI PERFIL RETRO VIBES</h1>
        <p class="subtitulo">Aquí puedes ver tu información, editar tu nombre, cambiar tu contraseña y revisar tus comentarios.</p>

        <div class="grid-perfil">

            <!-- DATOS -->
            <div class="card">
                <h2>DATOS DEL USUARIO</h2>

                <div class="perfil-dato">
                    <span>Nombre</span>
                    <strong><?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="perfil-dato">
                    <span>Correo</span>
                    <strong><?php echo htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="perfil-dato">
                    <span>Rol</span>
                    <strong><?php echo htmlspecialchars($usuario['role'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="perfil-dato">
                    <span>Miembro desde</span>
                    <strong><?php echo htmlspecialchars($usuario['creado_en'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="acciones">
                    <a class="btn btn-inicio" href="index.php">← Volver al inicio</a>
                    <a class="btn btn-logout" href="logout.php">Cerrar sesión</a>
                </div>
            </div>

            <!-- EDITAR PERFIL -->
            <div class="card">
                <h2>EDITAR PERFIL</h2>

                <?php if ($mensajePerfil): ?>
                    <div class="mensaje-ok"><?php echo htmlspecialchars($mensajePerfil, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if ($errorPerfil): ?>
                    <div class="mensaje-error"><?php echo htmlspecialchars($errorPerfil, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="form-group">
                        <label for="nombre">Cambiar nombre</label>
                        <input 
                            type="text" 
                            id="nombre" 
                            name="nombre" 
                            class="form-input"
                            maxlength="50"
                            value="<?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <button type="submit" name="actualizar_nombre" class="btn btn-guardar">
                        Guardar nuevo nombre
                    </button>
                </form>

                <br><br>

                <?php if ($mensajePassword): ?>
                    <div class="mensaje-ok"><?php echo htmlspecialchars($mensajePassword, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if ($errorPassword): ?>
                    <div class="mensaje-error"><?php echo htmlspecialchars($errorPassword, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="form-group">
                        <label for="password_actual">Contraseña actual</label>
                        <input 
                            type="password" 
                            id="password_actual" 
                            name="password_actual" 
                            class="form-input"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password_nueva">Nueva contraseña</label>
                        <input 
                            type="password" 
                            id="password_nueva" 
                            name="password_nueva" 
                            class="form-input"
                            minlength="6"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password_confirmar">Confirmar nueva contraseña</label>
                        <input 
                            type="password" 
                            id="password_confirmar" 
                            name="password_confirmar" 
                            class="form-input"
                            minlength="6"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="ayuda">
                        La nueva contraseña debe tener al menos 6 caracteres.
                    </div>

                    <button type="submit" name="cambiar_password" class="btn btn-password">
                        Cambiar contraseña
                    </button>
                </form>
            </div>

            <!-- COMENTARIOS -->
            <div class="card">
                <h2>MIS COMENTARIOS</h2>

                <?php if ($mensajeComentario): ?>
                    <div class="<?php echo $tipoMensajeComentario === 'ok' ? 'mensaje-ok' : 'mensaje-error'; ?>">
                        <?php echo htmlspecialchars($mensajeComentario, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($comentarios): ?>
                    <?php foreach ($comentarios as $c): ?>
                        <div class="comentario">
                            <div class="comentario-top">
                                <div><strong>Post:</strong> <?php echo htmlspecialchars($c['post_slug'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div><strong>Fecha:</strong> <?php echo htmlspecialchars($c['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div>
                                    <?php if ($c['status'] === 'visible'): ?>
                                        <span class="estado-visible">Visible</span>
                                    <?php else: ?>
                                        <span class="estado-oculto">Oculto</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="texto-comentario">
                                <?php echo nl2br(htmlspecialchars($c['comment_text'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>

                            <div class="comentario-acciones">
                                <a href="editar_comentario.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-editar">
                                    Editar comentario
                                </a>

                                <form method="POST" action="eliminar_comentario.php" onsubmit="return confirm('¿Seguro que deseas eliminar este comentario?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="comentario_id" value="<?php echo (int)$c['id']; ?>">
                                    <button type="submit" class="btn btn-eliminar">Eliminar comentario</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sin-comentarios">
                        No has realizado comentarios todavía.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>