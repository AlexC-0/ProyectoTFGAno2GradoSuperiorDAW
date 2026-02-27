<?php
declare(strict_types=1);

function ew_is_logged_in(): bool
{
    return isset($_SESSION['usuario_id']);
}

function ew_is_admin(): bool
{
    return !empty($_SESSION['es_admin']) && (int)$_SESSION['es_admin'] === 1;
}

function ew_require_login(string $redirect = 'login.php'): void
{
    if (!ew_is_logged_in()) {
        header("Location: $redirect");
        exit;
    }
}

function ew_require_admin(string $redirect = 'index.php'): void
{
    if (!ew_is_logged_in() || !ew_is_admin()) {
        header("Location: $redirect");
        exit;
    }
}
