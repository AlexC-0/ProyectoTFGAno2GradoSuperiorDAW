<?php
/*
DOCUMENTACION_PASO4
Este archivo agrupa control de acceso y roles.
- Verifica si hay sesion valida de usuario.
- Distingue permisos de administrador y zonas privadas.
- Se usa como guardia comun para bloquear accesos no autorizados.
*/
declare(strict_types=1);

// Devuelve true si existe sesion autenticada.
function ew_is_logged_in(): bool
{
    return isset($_SESSION['usuario_id']);
}

// Devuelve true solo para roles admin.
function ew_is_admin(): bool
{
    return !empty($_SESSION['es_admin']) && (int)$_SESSION['es_admin'] === 1;
}

// Guardia de acceso para paginas privadas de usuario.
// Redirige y corta ejecucion si no hay sesion.
function ew_require_login(string $redirect = 'login.php'): void
{
    if (!ew_is_logged_in()) {
        header("Location: $redirect");
        exit;
    }
}

// Guardia de acceso para paneles/admin endpoints.
// Exige login + rol admin.
function ew_require_admin(string $redirect = 'index.php'): void
{
    if (!ew_is_logged_in() || !ew_is_admin()) {
        header("Location: $redirect");
        exit;
    }
}

