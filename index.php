<?php

// ====== HEADERS SEGURIDAD ======
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ====== SESION SEGURA ======
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Retro Vibes | Blog de videojuegos retro</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header class="site-header">
<div class="container header-content">

<div class="logo">
<span class="logo-icon">🕹️</span>
<h1>Retro Vibes</h1>
</div>

<nav class="main-nav">
<a href="#inicio">Inicio</a>
<a href="#iconicos">Juegos Icónicos</a>
<a href="#articulos">Artículos</a>
<a href="#comunidad">Comunidad</a>
<a href="blog.php">Blog</a>

<?php if(isset($_SESSION['user_nombre'])): ?>

<span style="margin-left:20px;">
👤 <?php echo htmlspecialchars($_SESSION['user_nombre'], ENT_QUOTES, 'UTF-8'); ?>
</span>

<a href="perfil.php">Mi Perfil</a>
<a href="logout.php" style="margin-left:10px;">Cerrar sesión</a>

<?php else: ?>

<a href="login.php" style="margin-left:20px;">Login</a>

<?php endif; ?>

<?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
<a href="admin.php">Panel Admin</a>
<?php endif; ?>

</nav>
</div>
</header>

<main>

<section class="hero" id="inicio">
<div class="container hero-content">

<p class="tagline">Nostalgia, pixeles y grandes recuerdos</p>

<h2>
Un rincón para quienes crecieron entre consolas, arcade y juegos inolvidables.
</h2>

<p class="hero-text">
Retro Vibes es un espacio para recordar clásicos, compartir experiencias
y mantener viva la pasión por los videojuegos retro.
</p>

<?php if(!isset($_SESSION['user_nombre'])): ?>
<a href="login.php" class="primary-btn">Entrar al mundo retro</a>
<?php endif; ?>

</div>
</section>

<section class="featured-games" id="iconicos">
<div class="container">

<h2 class="section-title">Juegos Icónicos</h2>

<div class="cards-grid">

<article class="card">
<h3>Super Mario Bros</h3>
<p>
El clásico que marcó generaciones y convirtió cada nivel en una aventura inolvidable.
</p>
</article>

<article class="card">
<h3>Contra</h3>
<p>
Acción intensa, cooperación y dificultad legendaria en uno de los juegos más recordados.
</p>
</article>

<article class="card">
<h3>Street Fighter II</h3>
<p>
El rey de las peleas arcade, responsable de tardes enteras frente a la máquina.
</p>
</article>

</div>
</div>
</section>

<section class="latest-posts" id="articulos">
<div class="container">

<h2 class="section-title">Últimos Artículos</h2>

<div class="posts-grid">

<article class="post-card">
<span class="post-category">Arcade</span>

<div class="post-meta">
<span>Konami</span>
<span>•</span>
<span>5 min de lectura</span>
</div>

<h3>Por qué Contra sigue siendo brutal</h3>

<p class="post-excerpt">
Un vistazo a su dificultad, ritmo y la emoción de jugar en cooperativo.
</p>

<a href="post-contra.php" class="read-more">Leer artículo →</a>
</article>

<article class="post-card">
<span class="post-category">Nintendo</span>

<div class="post-meta">
<span>Nintendo</span>
<span>•</span>
<span>4 min de lectura</span>
</div>

<h3>Super Mario Bros y el inicio de una era</h3>

<p class="post-excerpt">
Cómo un juego aparentemente simple cambió la historia de los videojuegos.
</p>

<a href="post-mario.php" class="read-more">Leer artículo →</a>
</article>

<article class="post-card">
<span class="post-category">Fight Games</span>

<div class="post-meta">
<span>Capcom</span>
<span>•</span>
<span>6 min de lectura</span>
</div>

<h3>Street Fighter II y las retas inolvidables</h3>

<p class="post-excerpt">
Cada combate era una historia digna de recordar.
</p>

<a href="post-streetfighter.php" class="read-more">Leer artículo →</a>
</article>

<article class="post-card">
<span class="post-category">Zelda</span>

<div class="post-meta">
<span>Nintendo</span>
<span>•</span>
<span>6 min de lectura</span>
</div>

<h3>The Legend of Zelda y la aventura eterna</h3>

<p class="post-excerpt">
Exploración, secretos y libertad en uno de los juegos más legendarios.
</p>

<a href="post-zelda.php" class="read-more">Leer artículo →</a>
</article>

</div>
</div>
</section>

<section class="community" id="comunidad">
<div class="container community-box">

<h2>Comunidad Retro</h2>

<p>
Muy pronto podrás compartir tus recuerdos y comentar tus juegos favoritos.
</p>

<?php if(!isset($_SESSION['user_nombre'])): ?>
<a href="login.php" class="secondary-btn">Quiero participar</a>
<?php endif; ?>

</div>
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