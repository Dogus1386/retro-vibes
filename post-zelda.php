<?php

// ====== HEADERS DE SEGURIDAD ======
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once "auth.php";
require_once "db.php";

$post_slug = "zelda";

/* ======================
   TOKEN CSRF
====================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ======================
   GUARDAR COMENTARIO
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["comment_text"])) {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    if (isset($_SESSION['status']) && $_SESSION['status'] === 'bloqueado') {
        die('Tu cuenta está bloqueada y no puedes comentar.');
    }

    $comment_text = trim($_POST["comment_text"] ?? "");
    $author_name = trim($_SESSION["user_nombre"] ?? "");
    $usuario_id = (int)($_SESSION["user_id"] ?? 0);

    if ($usuario_id <= 0 || $author_name === '') {
        die('Sesión no válida.');
    }

    if ($comment_text === "") {
        die("El comentario no puede ir vacío.");
    }

    if (mb_strlen($comment_text) > 500) {
        die("El comentario no puede superar los 500 caracteres.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO comments (post_slug, author_name, comment_text, created_at, usuario_id, status)
        VALUES (?, ?, ?, NOW(), ?, 'visible')
    ");
    $stmt->execute([$post_slug, $author_name, $comment_text, $usuario_id]);

    header("Location: post-zelda.php");
    exit;
}

/* ======================
   CARGAR COMENTARIOS SOLO DE ZELDA
====================== */
$usuarioIdActual = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT 
        c.*,
        (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) AS total_likes,
        (SELECT COUNT(*) FROM comment_likes cl2 WHERE cl2.comment_id = c.id AND cl2.usuario_id = ?) AS usuario_dio_like
    FROM comments c
    WHERE c.post_slug = ? AND c.status = 'visible'
    ORDER BY c.id DESC
");
$stmt->execute([$usuarioIdActual, $post_slug]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Retro Vibes | The Legend of Zelda y la aventura eterna</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Inter:wght@400;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />

  <style>
    .comment-actions { margin-top: 12px; }
    .like-section { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .like-form { margin:0; }
    .like-btn {
      background:linear-gradient(135deg,#1e293b,#334155);
      color:#fff;
      border:1px solid #475569;
      padding:8px 14px;
      border-radius:10px;
      cursor:pointer;
      font-weight:700;
      transition:.25s ease;
    }
    .like-btn:hover {
      transform:translateY(-1px);
      box-shadow:0 0 12px rgba(255,255,255,.12);
    }
    .like-btn.liked {
      background:linear-gradient(135deg,#e11d48,#fb7185);
      border-color:#fb7185;
    }
    .like-count {
      color:#facc15;
      font-weight:700;
      font-size:14px;
    }
    .like-login-msg {
      color:#94a3b8;
      font-size:14px;
    }
    #contador {
      margin-top:8px;
      font-size:14px;
      color:#94a3b8;
    }
  </style>
</head>
<body>
<header class="site-header">
  <div class="container header-content">
    <div class="logo">
      <span class="logo-icon">🕹️</span>
      <h1>Retro Vibes</h1>
    </div>

    <nav class="main-nav">
      <a href="index.php">Inicio</a>
      <a href="index.php#iconicos">Juegos Icónicos</a>
      <a href="blog.php">Blog</a>
      <a href="index.php#comunidad">Comunidad</a>

      <?php if (isset($_SESSION['user_nombre'])): ?>
        <span style="margin-left:20px;">👤 <?php echo htmlspecialchars($_SESSION['user_nombre'], ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="perfil.php" style="margin-left:10px;">Mi perfil</a>
        <a href="logout.php" style="margin-left:10px;">Cerrar sesión</a>
      <?php else: ?>
        <a href="login.php" style="margin-left:20px;">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main>
  <section class="post-hero">
    <div class="container post-hero-content">
      <nav class="breadcrumb">
        <a href="index.php">Inicio</a>
        <span>/</span>
        <a href="blog.php">Blog</a>
        <span>/</span>
        <span class="breadcrumb-current">Zelda</span>
      </nav>

      <span class="post-category">Adventure</span>

      <div class="post-meta">
        <span>Nintendo</span>
        <span>•</span>
        <span>1986</span>
        <span>•</span>
        <span>5 min de lectura</span>
      </div>

      <h2 class="post-title">The Legend of Zelda y la aventura eterna</h2>

      <p class="post-intro">
        Zelda abrió la puerta a un tipo de aventura que se sentía enorme, misteriosa y llena de secretos.
        Fue un clásico que enseñó a explorar sin miedo.
      </p>
    </div>
  </section>

  <section class="post-content-section">
    <div class="container post-layout">
      <article class="post-content">
        <div class="post-image">
          <img src="assets/img/zelda1.jpg" alt="The Legend of Zelda clásico retro">
        </div>

        <p>
          El primer The Legend of Zelda fue especial porque no se limitó a llevar al jugador de un punto a otro.
          Lo soltaba en un mundo abierto para la época y lo invitaba a descubrir por sí mismo qué hacer, a dónde ir
          y qué peligros enfrentar.
        </p>

        <p>
          Esa sensación de aventura real fue parte de su magia. No era un juego que lo explicaba todo. Te obligaba a observar,
          probar, equivocarte y volver a intentarlo, y eso lo hacía memorable.
        </p>

        <h3>Exploración, secretos y libertad</h3>

        <p>
          Zelda destacó por su estructura abierta. Había cuevas escondidas, caminos secretos, enemigos peligrosos
          y objetos que cambiaban la forma de jugar. Cada descubrimiento se sentía importante.
        </p>

        <p>
          El jugador no solo avanzaba: investigaba, recordaba lugares y construía su propio mapa mental del reino.
          Eso fue revolucionario y ayudó a definir el género de aventura.
        </p>

        <div class="retro-note">
          <h4>Dato retro</h4>
          <p>
            El juego se lanzó originalmente para Famicom Disk System en Japón, lo que permitió guardar partida,
            algo muy llamativo para su tiempo.
          </p>
        </div>

        <h3>Por qué sigue siendo legendario</h3>

        <p>
          Su legado sigue vivo porque puso las bases de una franquicia gigantesca y de muchas ideas que hoy son normales
          en juegos de aventura y exploración.
        </p>

        <p>
          Zelda no solo fue un clásico: fue una invitación a perderse en una aventura inolvidable.
        </p>

        <a href="blog.php" class="back-link">← Volver al blog</a>
      </article>

      <aside class="post-sidebar">
        <div class="sidebar-box">
          <h3>Ficha rápida</h3>
          <ul class="sidebar-list">
            <li><strong>Juego:</strong> The Legend of Zelda</li>
            <li><strong>Compañía:</strong> Nintendo</li>
            <li><strong>Año:</strong> 1986</li>
            <li><strong>Género:</strong> Aventura</li>
            <li><strong>Modo:</strong> 1 jugador</li>
          </ul>
        </div>
      </aside>
    </div>

    <section class="comments-section">
      <h2>Comentarios retro</h2>

      <?php if (isset($_SESSION['user_id'])): ?>
        <form method="POST" action="" class="comment-form">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

          <div class="form-group">
            <label for="commentText">Tu comentario</label>
            <textarea
              id="commentText"
              name="comment_text"
              maxlength="500"
              placeholder="Comparte tu recuerdo sobre Zelda..."
              required
            ></textarea>
            <p id="contador">0 / 500</p>
          </div>

          <button type="submit" class="primary-btn">Publicar comentario</button>
        </form>
      <?php else: ?>
        <p>Debes iniciar sesión para comentar.</p>
        <p><a href="login.php">Iniciar sesión</a> o <a href="registro.php">registrarte</a></p>
      <?php endif; ?>

      <div class="comments-list">
        <?php if (count($comments) > 0): ?>
          <?php foreach ($comments as $comment): ?>
            <div class="comment-card">
              <div class="comment-header">
                <div class="comment-avatar">
                  <?php echo htmlspecialchars(strtoupper(substr($comment["author_name"], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <div class="comment-meta">
                  <h4><?php echo htmlspecialchars($comment["author_name"], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <span><?php echo htmlspecialchars($comment["created_at"], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>

              <p><?php echo nl2br(htmlspecialchars($comment["comment_text"], ENT_QUOTES, 'UTF-8')); ?></p>

              <div class="comment-actions">
                <div class="like-section">
                  <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="toggle_like.php" method="POST" class="like-form">
                      <input type="hidden" name="comment_id" value="<?php echo (int)$comment['id']; ?>">
                      <input type="hidden" name="redirect" value="post-zelda.php">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                      <button type="submit" class="like-btn <?php echo ((int)$comment['usuario_dio_like'] > 0) ? 'liked' : ''; ?>">
                        <?php echo ((int)$comment['usuario_dio_like'] > 0) ? '❤️ Te gusta' : '🤍 Me gusta'; ?>
                      </button>
                    </form>
                  <?php else: ?>
                    <div class="like-login-msg">Inicia sesión para dar like</div>
                  <?php endif; ?>

                  <div class="like-count">
                    <?php echo (int)$comment['total_likes']; ?> like<?php echo ((int)$comment['total_likes'] !== 1) ? 's' : ''; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="empty-comments">Aún no hay comentarios. Sé el primero en compartir tu recuerdo retro.</p>
        <?php endif; ?>
      </div>
    </section>
  </section>
</main>

<footer class="site-footer">
  <div class="container">
    <p>© <span id="year"></span> Retro Vibes - Blog de videojuegos retro</p>
  </div>
</footer>

<script src="assets/js/app.js"></script>
<script>
const commentText = document.getElementById('commentText');
const contador = document.getElementById('contador');

if (commentText && contador) {
  const actualizarContador = () => {
    contador.textContent = commentText.value.length + ' / 500';
  };

  commentText.addEventListener('input', actualizarContador);
  actualizarContador();
}
</script>
</body>
</html>