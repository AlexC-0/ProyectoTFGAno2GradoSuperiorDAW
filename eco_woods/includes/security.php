<?php
declare(strict_types=1);

// Escape HTML centralizado para evitar XSS en vistas.
// Se usa siempre que se imprime contenido dinamico.
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Genera (o reutiliza) el token CSRF de sesion.
// Se llama desde formularios y JS para incluir el token en requests.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes proporciona entropia criptografica.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Compara token recibido vs token de sesion en tiempo constante
// (hash_equals) para evitar ataques de timing.
function csrf_validate(?string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && $sessionToken !== '' && hash_equals($sessionToken, $token);
}
