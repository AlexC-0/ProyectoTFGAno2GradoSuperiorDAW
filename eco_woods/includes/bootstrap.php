<?php
/*
DOCUMENTACION_PASO4
Este archivo inicializa la base comun del proyecto.
- Arranca la sesion de forma segura y consistente para todas las paginas.
- Carga utilidades compartidas (escape, csrf, helpers basicos).
- Evita que cada archivo tenga que repetir arranque y configuracion manual.
*/
declare(strict_types=1);

// Punto unico de arranque para sesiones y utilidades de seguridad.
// Se incluye al principio de cada script para evitar configuraciones
// de sesion duplicadas o inconsistentes entre paginas.
if (session_status() === PHP_SESSION_NONE) {
    // Si la peticion llega por HTTPS, marcamos la cookie como secure.
    // En local HTTP esto queda en false para no romper la sesion.
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // Politica de cookie de sesion:
    // - lifetime 0: solo sesion del navegador.
    // - httponly: evita acceso desde JS (mitiga robo por XSS).
    // - samesite Lax: reduce CSRF en navegacion cruzada.
    // - secure: solo por HTTPS cuando aplica.
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Carga de helpers comunes de seguridad (escape HTML, CSRF, etc.).
require_once __DIR__ . '/security.php';

