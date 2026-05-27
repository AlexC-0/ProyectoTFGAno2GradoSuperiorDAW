<?php

declare(strict_types=1);

$estados_mueble = ["Mal estado", "Correcto", "Buen estado", "Muy buen estado", "Perfecto"];
$categorias = ["Mesa", "Armario", "Silla", "Cama", "Estantería", "Sofá", "Otro"];
$tipos_recambio = ["Bisagra", "Tirador", "Pata", "Tope", "Junta", "Soporte", "Guía", "Pieza estructural", "Otro"];
$compatibles_recambio = ["Mesa", "Armario", "Silla", "Cama", "Estantería", "Sofá", "Otro"];

function ew_valid_text(string $value, int $min, int $max): bool
{
    $value = trim($value);
    $len = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    return $len >= $min && $len <= $max;
}

function ew_text_length(string $value): int
{
    $value = trim($value);
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function ew_normalize_text_input(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return $value ?? '';
}

function ew_letters_count(string $value): int
{
    preg_match_all('/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/u', $value, $matches);
    return count($matches[0]);
}

function ew_words_count(string $value): int
{
    preg_match_all('/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]{3,}/u', $value, $matches);
    return count($matches[0]);
}

function ew_has_clear_vowels(string $value, int $min): bool
{
    preg_match_all('/[AEIOUÁÉÍÓÚÜaeiouáéíóúü]/u', $value, $matches);
    return count($matches[0]) >= $min;
}

function ew_has_allowed_plain_text_chars(string $value): bool
{
    return (bool)preg_match('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9\s.,;:()\/+\-ºª€]+$/u', $value);
}

function ew_has_repeated_gibberish(string $value): bool
{
    if (preg_match('/(.)\1{3,}/u', $value)) {
        return true;
    }

    return (bool)preg_match('/[bcdfghjklmnñpqrstvwxyzBCDFGHJKLMNÑPQRSTVWXYZ]{6,}/u', $value);
}

function ew_valid_title_text(string $value, int $min, int $max): bool
{
    $value = ew_normalize_text_input($value);

    if (!ew_valid_text($value, $min, $max)) {
        return false;
    }

    if (!ew_has_allowed_plain_text_chars($value)) {
        return false;
    }

    if (preg_match('/^\d+([,.]\d+)?$/', $value)) {
        return false;
    }

    if (ew_letters_count($value) < 5 || ew_words_count($value) < 2 || !ew_has_clear_vowels($value, 2)) {
        return false;
    }

    return !ew_has_repeated_gibberish($value);
}

function ew_valid_place_text(string $value): bool
{
    $value = ew_normalize_text_input($value);

    if (!ew_valid_text($value, 2, 80)) {
        return false;
    }

    if (!preg_match('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.\'-]+$/u', $value)) {
        return false;
    }

    if (ew_letters_count($value) < 2 || !ew_has_clear_vowels($value, 1)) {
        return false;
    }

    return !ew_has_repeated_gibberish($value);
}

function ew_valid_description_text(string $value): bool
{
    $value = ew_normalize_text_input($value);
    $len = ew_text_length($value);

    if ($len < 40 || $len > 1000) {
        return false;
    }

    if (!ew_has_allowed_plain_text_chars($value)) {
        return false;
    }

    if (ew_words_count($value) < 8 || ew_letters_count($value) < 25 || !ew_has_clear_vowels($value, 8)) {
        return false;
    }

    if (ew_has_repeated_gibberish($value)) {
        return false;
    }

    $wordCharacters = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/u', '', $value);
    $cleanLength = ew_text_length($wordCharacters);
    if ($cleanLength > 0) {
        preg_match_all('/[AEIOUÁÉÍÓÚÜaeiouáéíóúü]/u', $wordCharacters, $vowels);
        $vowelRatio = count($vowels[0]) / $cleanLength;
        if ($vowelRatio < 0.25 || $vowelRatio > 0.70) {
            return false;
        }
    }

    return true;
}

function ew_valid_decimal_price(string $value): bool
{
    if (!preg_match('/^\d{1,7}([,.]\d{1,2})?$/', $value)) {
        return false;
    }

    $price = (float)str_replace(',', '.', $value);
    return $price > 0 && $price <= 9999999.99;
}
