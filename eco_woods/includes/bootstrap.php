<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/security.php';
