<?php
declare(strict_types=1);

function ew_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function ew_json_ok(string $message, array $extra = []): void
{
    ew_json(array_merge(['ok' => true, 'message' => $message], $extra));
}

function ew_json_error(string $message, int $status = 400, array $extra = []): void
{
    ew_json(array_merge(['ok' => false, 'message' => $message], $extra), $status);
}
