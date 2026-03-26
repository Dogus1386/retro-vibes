<?php

// Evitar incluirlo dos veces
if (defined('RETRO_VIBES_SECURITY_LOADED')) {
    return;
}
define('RETRO_VIBES_SECURITY_LOADED', true);

/* ======================
   HEADERS DE SEGURIDAD
====================== */
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* ======================
   SESION SEGURA
====================== */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/* ======================
   TOKEN CSRF
====================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ======================
   FUNCIONES DE APOYO
====================== */

function csrf_token()
{
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_input()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function validar_csrf()
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        die('Solicitud no válida');
    }
}

function usuario_logueado()
{
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function usuario_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function usuario_bloqueado()
{
    return isset($_SESSION['status']) && $_SESSION['status'] === 'bloqueado';
}

function redirigir($url)
{
    header("Location: $url");
    exit;
}

function limpiar_texto($valor)
{
    return trim((string)$valor);
}

function escapar($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function validar_comentario($texto, $max = 500)
{
    $texto = trim((string)$texto);

    if ($texto === '') {
        return 'El comentario no puede ir vacío.';
    }

    if (mb_strlen($texto) > $max) {
        return "El comentario no puede superar los $max caracteres.";
    }

    return '';
}

function validar_nombre_usuario($nombre)
{
    $nombre = trim((string)$nombre);

    if ($nombre === '') {
        return 'El nombre no puede ir vacío.';
    }

    if (mb_strlen($nombre) < 3) {
        return 'El nombre debe tener al menos 3 caracteres.';
    }

    if (mb_strlen($nombre) > 50) {
        return 'El nombre no puede superar los 50 caracteres.';
    }

    if (!preg_match('/^[\p{L}\p{N} ]+$/u', $nombre)) {
        return 'El nombre solo puede contener letras, números y espacios.';
    }

    return '';
}

function validar_password_nueva($password)
{
    $password = (string)$password;

    if ($password === '') {
        return 'La contraseña no puede ir vacía.';
    }

    if (strlen($password) < 6) {
        return 'La contraseña debe tener al menos 6 caracteres.';
    }

    if (strlen($password) > 100) {
        return 'La contraseña es demasiado larga.';
    }

    return '';
}

/* ======================
   LIMITADOR SIMPLE ANTI FLOOD
====================== */
function anti_flood($clave, $segundos = 5)
{
    $ahora = time();

    if (!isset($_SESSION['anti_flood'])) {
        $_SESSION['anti_flood'] = [];
    }

    $ultimo = $_SESSION['anti_flood'][$clave] ?? 0;

    if (($ahora - $ultimo) < $segundos) {
        return false;
    }

    $_SESSION['anti_flood'][$clave] = $ahora;
    return true;
}