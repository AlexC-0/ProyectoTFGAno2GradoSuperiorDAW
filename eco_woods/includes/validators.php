<?php
declare(strict_types=1);

function ew_post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function ew_get_int(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
}

function ew_post_string(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}
