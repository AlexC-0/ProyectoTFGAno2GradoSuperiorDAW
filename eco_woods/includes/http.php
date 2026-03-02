<?php
/*
DOCUMENTACION_PASO4
Este archivo normaliza respuestas HTTP y JSON.
- Permite devolver exito o error con formato estable.
- Facilita que frontend y backend hablen el mismo idioma.
- Reduce codigo repetido en endpoints y APIs.
*/
declare(strict_types=1);

// Helper de salida JSON centralizada.
// Garantiza:
// - codigo HTTP correcto
// - content-type uniforme
// - salida y corte de ejecucion en un solo punto
function ew_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

// Respuesta de exito estandar del proyecto.
function ew_json_ok(string $message, array $extra = []): void
{
    ew_json(array_merge(['ok' => true, 'message' => $message], $extra));
}

// Respuesta de error estandar del proyecto.
// Permite incluir codigo HTTP semantico y campos extra.
function ew_json_error(string $message, int $status = 400, array $extra = []): void
{
    ew_json(array_merge(['ok' => false, 'message' => $message], $extra), $status);
}

