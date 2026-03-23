<?php
/*
DOCUMENTACION_PASO4
Este archivo inicializa la base comun del proyecto.
- Arranca la sesion de forma segura y consistente para todas las paginas.
- Carga utilidades compartidas (escape, csrf, helpers basicos).
- Evita que cada archivo tenga que repetir arranque y configuracion manual.
*/

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

function ew_normalize_html_output(string $buffer): string
{
    $trimmed = ltrim($buffer);
    if ($trimmed === '' || (stripos($trimmed, '<!DOCTYPE html') !== 0 && stripos($trimmed, '<html') !== 0)) {
        return $buffer;
    }

    $tokens = [
        'á', 'é', 'í', 'ó', 'ú',
        'Á', 'É', 'Í', 'Ó', 'Ú',
        'ñ', 'Ñ', 'ü', 'Ü',
        '€', '—', '–', '“', '”', '‘', '’', '•',
        '★', '☆', '▲', '¿', '¡',
    ];

    $map = [];
    foreach ($tokens as $token) {
        $broken = @mb_convert_encoding($token, 'UTF-8', 'Windows-1252');
        if (is_string($broken) && $broken !== '' && $broken !== $token) {
            $map[$broken] = $token;
        }
    }

    $buffer = strtr($buffer, $map);
    return str_replace('Â', '', $buffer);
}

if (!defined('EW_HTML_NORMALIZER_STARTED')) {
    define('EW_HTML_NORMALIZER_STARTED', true);
    ob_start('ew_normalize_html_output');
}
