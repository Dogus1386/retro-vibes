<?php

// ====== HEADERS DE SEGURIDAD ======
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once 'auth_public.php';
require_once 'db.php';

$post_slug = "streetfighter";

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

    header("Location: post-streetfighter.php");
    exit;
}

/* ======================
   CARGAR COMENTARIOS SOLO DE STREET FIGHTER
====================== */
$usuarioIdActual = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT 
        c.*,
        (
            SELECT COUNT(*) 
            FROM comment_likes cl 
            WHERE cl.comment_id = c.id
        ) AS total_likes,
        (
            SELECT COUNT(*) 
            FROM comment_likes cl2 
            WHERE cl2.comment_id = c.id AND cl2.usuario_id = ?
        ) AS usuario_dio_like
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
  <title>Retro Vibes | Street Fighter II y las retas inolvidables</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Inter:wght@400;700;800&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="assets/css/style.css" />

  <style>
    .comment-actions {
      margin-top: 12px;
    }

    .like-section {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .like-form {
      margin: 0;
    }

    .like-btn {
      background: linear-gradient(135deg, #1e293b, #334155);
      color: #fff;
      border: 1px solid #475569;
      padding: 8px 14px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 700;
      transition: 0.25s ease;
    }

    .like-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 0 12px rgba(255, 255, 255, 0.12);
    }

    .like-btn.liked {
      background: linear-gradient(135deg, #e11d48, #fb7185);
      border-color: #fb7185;
      color: #fff;
    }

    .like-count {
      color: #facc15;
      font-weight: 700;
      font-size: 14px;
    }

    .like-login-msg {
      color: #94a3b8;
      font-size: 14px;
    }

    #contador {
      margin-top: 8px;
      font-size: 14px;
      color: #94a3b8;
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
          <span class="breadcrumb-current">Street Fighter II</span>
        </nav>

        <span class="post-category">Fight Games</span>

        <div class="post-meta">
          <span>Capcom</span>
          <span>•</span>
          <span>1991</span>
          <span>•</span>
          <span>5 min de lectura</span>
        </div>

        <h2 class="post-title">Street Fighter II y las retas inolvidables</h2>

        <p class="post-intro">
          Street Fighter II no fue solo un juego de pelea: fue un fenómeno que llenó
          arcades, creó rivalidades memorables y convirtió las retas entre amigos en
          parte esencial de la cultura gamer.
        </p>
      </div>
    </section>

    <section class="post-content-section">
      <div class="container post-layout">
        <article class="post-content">
          <div class="post-image">
            <img src="assets/img/streetfighter1.jpg" alt="Street Fighter II juego clásico de pelea Capcom">
          </div>

          <p>
            Street Fighter II cambió el género de pelea para siempre. Antes de su
            llegada ya existían juegos de combate, pero ninguno logró combinar
            personajes memorables, movimientos especiales y un sistema competitivo
            tan sólido como el que presentó Capcom.
          </p>

          <p>
            Cada personaje tenía su propio estilo, personalidad y técnicas únicas.
            Eso hacía que cada jugador encontrara un favorito y desarrollara su
            propia manera de pelear. Ryu, Ken, Chun-Li, Guile, Blanka... todos
            dejaron huella.
          </p>

          <h3>El rey de las retas</h3>

          <p>
            Parte de la magia de Street Fighter II estaba en el enfrentamiento
            directo contra otra persona. No era solo ganarle a la máquina: era
            medirte contra tu amigo, tu primo o cualquier rival que se acercara al
            arcade dispuesto a demostrar que jugaba mejor.
          </p>

          <p>
            Las retas creaban momentos intensos, gritos, risas y también mucha
            rivalidad sana. Ganar una pelea cerrada con un último golpe especial
            era una sensación inolvidable.
          </p>

          <div class="retro-note">
            <h4>Dato retro</h4>
            <p>
              Street Fighter II fue tan exitoso que tuvo varias versiones mejoradas,
              algo que ayudó a mantenerlo vivo durante años en arcades y consolas.
            </p>
          </div>

          <h3>Movimientos que se volvieron leyenda</h3>

          <p>
            El Hadouken, el Shoryuken y el Sonic Boom no eran solo ataques:
            eran parte del lenguaje gamer de toda una generación. Aprender a hacer
            esos movimientos especiales daba una enorme satisfacción y elevaba el
            nivel de cada partida.
          </p>

          <p>
            Street Fighter II también premió la práctica. Mientras más jugabas,
            más entendías tiempos, distancias, bloqueos y contraataques. Eso lo
            convirtió en un juego profundo y duradero.
          </p>

          <h3>Por qué sigue siendo un clásico</h3>

          <p>
            Su legado sigue presente porque definió muchas de las bases que todavía
            usan los juegos de pelea modernos. Balance, carisma, competencia y
            emoción pura: Street Fighter II tenía todo eso.
          </p>

          <p>
            Por eso sigue siendo inolvidable. Porque no solo marcó una época:
            ayudó a construir una comunidad entera alrededor de las retas.
          </p>

          <a href="blog.php" class="back-link">← Volver al blog</a>
        </article>

        <aside class="post-sidebar">
          <div class="sidebar-box">
            <h3>Ficha rápida</h3>
            <ul class="sidebar-list">
              <li><strong>Juego:</strong> Street Fighter II</li>
              <li><strong>Compañía:</strong> Capcom</li>
              <li><strong>Año:</strong> 1991</li>
              <li><strong>Género:</strong> Pelea</li>
              <li><strong>Modo:</strong> 1 o 2 jugadores</li>
            </ul>
          </div>

          <div class="sidebar-box">
            <h3>Temas relacionados</h3>
            <ul class="sidebar-list">
              <li><a href="#">Arcades clásicos</a></li>
              <li><a href="#">Juegos de pelea</a></li>
              <li><a href="#">Retas entre amigos</a></li>
              <li><a href="#">Capcom legendario</a></li>
            </ul>
          </div>
        </aside>
      </div>

      <div class="post-navigation">
        <a href="post-mario.php" class="nav-post prev">
          ← Artículo anterior
          <span>Super Mario Bros y el inicio de una era</span>
        </a>
      </div>

      <section class="related-posts">
        <h2>Artículos relacionados</h2>

        <div class="related-grid">
          <a href="post-contra.php" class="related-card">
            <span class="tag">Arcade</span>
            <h3>Por qué Contra sigue siendo brutal</h3>
          </a>

          <a href="post-mario.php" class="related-card">
            <span class="tag">Nintendo</span>
            <h3>Super Mario Bros y el inicio de una era</h3>
          </a>
        </div>
      </section>

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
                placeholder="Comparte tu recuerdo sobre Street Fighter..."
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
                        <input type="hidden" name="redirect" value="post-streetfighter.php">
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