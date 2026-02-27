<?php
declare(strict_types=1);

// Lectura segura de enteros por POST.
// Centralizar este patron evita repetir isset/cast en cada endpoint.
function ew_post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

// Lectura segura de enteros por GET.
function ew_get_int(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
}

// Lectura segura de texto por POST con trim.
function ew_post_string(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}
