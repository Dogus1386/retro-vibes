
<?php
require_once "db.php";

$postSlug = "contra";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $authorName = trim($_POST["author_name"] ?? "");
    $commentText = trim($_POST["comment_text"] ?? "");

    if ($authorName !== "" && $commentText !== "") {
        $stmt = $pdo->prepare("INSERT INTO comments (post_slug, author_name, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$postSlug, $authorName, $commentText]);
    }

    header("Location: post-contra.php");
    exit;
}

$stmt = $pdo->prepare("SELECT author_name, comment_text, created_at FROM comments WHERE post_slug = ? ORDER BY id DESC");
$stmt->execute([$postSlug]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>




<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Retro Vibes | Por qué Contra sigue siendo brutal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Inter:wght@400;700;800&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-content">
      <div class="logo">
        <span class="logo-icon">🕹️</span>
        <h1>Retro Vibes</h1>
      </div>

      <nav class="main-nav">
        <a href="index.html">Inicio</a>
        <a href="index.html#iconicos">Juegos Icónicos</a>
        <a href="blog.html">Blog</a>
        <a href="index.html#comunidad">Comunidad</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="post-hero">
      <div class="container post-hero-content">
        <nav class="breadcrumb">
  <a href="index.html">Inicio</a>
  <span>/</span>
  <a href="blog.html">Blog</a>
  <span>/</span>
  <span class="breadcrumb-current">Contra</span>
</nav>
        <span class="post-category">Arcade</span>

        <div class="post-meta">
          <span>Konami</span>
          <span>•</span>
          <span>1987</span>
          <span>•</span>
          <span>5 min de lectura</span>
        </div>

        <h2 class="post-title">Por qué Contra sigue siendo brutal</h2>

        <p class="post-intro">
          Contra no solo fue un juego difícil. Fue una prueba de reflejos, memoria,
          coordinación y paciencia. Décadas después, sigue siendo un símbolo del
          desafío arcade bien hecho.
        </p>
      </div>
    </section>

    <section class="post-content-section">
      <div class="container post-layout">
        <article class="post-content">
            <div class="post-image">
  <img src="assets/img/contra1.jpg" alt="Contra arcade juego clásico Konami">
</div>
        
          <p>
            Hablar de Contra es hablar de una época en la que los videojuegos no
            perdonaban errores. Cada enemigo, cada salto y cada proyectil exigían
            atención total. No había espacio para distracciones, y justamente por
            eso cada avance se sentía ganado.
          </p>

          <p>
            Uno de los grandes méritos del juego fue su ritmo. Contra no daba tregua,
            pero tampoco se sentía injusto. Era rápido, intenso y exigente, pero al
            mismo tiempo te enseñaba a mejorar a través de la repetición. Perder era
            frustrante, sí, pero también era una invitación a intentarlo una vez más.
          </p>

          <h3>La dificultad que marcó una generación</h3>

          <p>
            Muchos jugadores recuerdan Contra por su dificultad legendaria. Sin embargo,
            esa dificultad no era gratuita. Formaba parte de la identidad del juego.
            Cada fase tenía enemigos colocados de forma estratégica, obligando al
            jugador a aprender patrones y reaccionar con precisión.
          </p>

          <p>
            Esto convirtió a Contra en una experiencia memorable. No era solo disparar;
            era sobrevivir, adaptarse y dominar el escenario. Esa mezcla de acción y
            tensión fue la que lo hizo inolvidable.
          </p>

          <div class="retro-note">
            <h4>Dato retro</h4>
            <p>
              Una de las razones por las que Contra se volvió tan famoso fue por el
              legendario “código Konami”, que daba vidas extra y se convirtió en parte
              de la cultura gamer.
            </p>
          </div>

          <h3>El cooperativo: caos, risas y coordinación</h3>

          <p>
            Jugar Contra solo era desafiante. Jugarlo con otra persona era otra historia.
            El modo cooperativo añadía emoción, estrategia y también bastante caos.
            Coordinar disparos, avanzar juntos y sobrevivir a la pantalla llena de
            enemigos convertía cada partida en una experiencia intensa y divertida.
          </p>

          <p>
            Esa experiencia compartida es una de las razones por las que Contra sigue
            vivo en la memoria de tantos jugadores. No era solo un juego difícil:
            era un juego que generaba historias.
          </p>

          <h3>Por qué sigue siendo recordado hoy</h3>

          <p>
            En una época actual donde muchos juegos ofrecen ayudas, checkpoints
            generosos y experiencias más guiadas, Contra representa una filosofía
            distinta: la recompensa de superar un reto real.
          </p>

          <p>
            Por eso sigue siendo brutal. Porque aún hoy transmite intensidad, carácter
            y una sensación de logro que pocos juegos consiguen replicar con la misma
            fuerza.
          </p>

          <a href="blog.html" class="back-link">← Volver al blog</a>
        </article>

        <aside class="post-sidebar">
          <div class="sidebar-box">
            <h3>Ficha rápida</h3>
            <ul class="sidebar-list">
              <li><strong>Juego:</strong> Contra</li>
              <li><strong>Compañía:</strong> Konami</li>
              <li><strong>Año:</strong> 1987</li>
              <li><strong>Género:</strong> Run and Gun</li>
              <li><strong>Modo:</strong> 1 o 2 jugadores</li>
            </ul>
          </div>

          <div class="sidebar-box">
            <h3>Temas relacionados</h3>
            <ul class="sidebar-list">
              <li><a href="#">Arcade clásico</a></li>
              <li><a href="#">Dificultad retro</a></li>
              <li><a href="#">Cooperativo local</a></li>
              <li><a href="#">Código Konami</a></li>
            </ul>
          </div>
        </aside>
      </div>

      <div class="post-navigation">

    <a href="post-metalslug.html" class="nav-post prev">
        ← Artículo anterior
        <span>Metal Slug y el arte del caos</span>
    </a>

    <a href="post-mario.html" class="nav-post next">
        Artículo siguiente →
        <span>Super Mario Bros y el inicio de una era</span>
    </a>

</div>


      <!-- ARTICULOS RELACIONADOS -->
<section class="related-posts">

    <h2>Artículos relacionados</h2>

    <div class="related-grid">

        <a href="post-mario.html" class="related-card">
            <span class="tag">Nintendo</span>
            <h3>Super Mario Bros y el inicio de una era</h3>
        </a>

        <a href="post-streetfighter.html" class="related-card">
            <span class="tag">Fight Games</span>
            <h3>Street Fighter II y las retas inolvidables</h3>
        </a>

        <a href="post-metalslug.html" class="related-card">
            <span class="tag">Run and Gun</span>
            <h3>Metal Slug y el arte del caos</h3>
        </a>

    </div>

</section>

<section class="comments-section">
  <h2>Comentarios retro</h2>

  <form method="POST" action="" class="comment-form">
    <div class="form-group">
      <label for="commentName">Nombre</label>
      <input type="text" id="commentName" name="author_name" placeholder="Ej: Julio" required>
    </div>

    <div class="form-group">
      <label for="commentText">Tu comentario</label>
      <textarea id="commentText" name="comment_text" rows="5" placeholder="Comparte tu recuerdo sobre Contra..." required></textarea>
    </div>

    <button type="submit" class="primary-btn">Publicar comentario</button>
  </form>

 <div class="comments-list">
  <?php if (count($comments) > 0): ?>
    <?php foreach ($comments as $comment): ?>
      <div class="comment-card">
        <div class="comment-header">
          <div class="comment-avatar">
            <?php echo strtoupper(substr($comment["author_name"], 0, 1)); ?>
          </div>

          <div class="comment-meta">
            <h4><?php echo htmlspecialchars($comment["author_name"]); ?></h4>
            <span><?php echo htmlspecialchars($comment["created_at"]); ?></span>
          </div>
        </div>

        <p><?php echo nl2br(htmlspecialchars($comment["comment_text"])); ?></p>
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
</body>
</html>

