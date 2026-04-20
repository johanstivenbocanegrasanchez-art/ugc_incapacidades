<?php

declare(strict_types=1);

namespace Core;

final class Validator
{
    private array $errors = [];

    public static function make(): self
    {
        return new self();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function required(string $value, string $field, string $message = ''): self
    {
        if (trim($value) === '') {
            $this->errors[$field] = $message ?: "El campo {$field} es obligatorio.";
        }
        return $this;
    }

    public function maxLength(string $value, int $max, string $field, string $message = ''): self
    {
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = $message ?: "El campo {$field} no puede exceder {$max} caracteres.";
        }
        return $this;
    }

    public function inWhitelist(string $value, array $whitelist, string $field, string $message = ''): self
    {
        if ($value !== '' && !in_array($value, $whitelist, true)) {
            $this->errors[$field] = $message ?: "El valor de {$field} no es válido.";
        }
        return $this;
    }

    public function date(string $value, string $field, string $format = 'Y-m-d', string $message = ''): self
    {
        if ($value === '') {
            return $this;
        }
        $d = \DateTime::createFromFormat($format, $value);
        if (!$d || $d->format($format) !== $value) {
            $this->errors[$field] = $message ?: "El campo {$field} debe ser una fecha válida ({$format}).";
        }
        return $this;
    }

    public function dateAfter(string $start, string $end, string $field, string $message = ''): self
    {
        if ($start === '' || $end === '') {
            return $this;
        }
        if ($end < $start) {
            $this->errors[$field] = $message ?: "La fecha fin no puede ser anterior a la fecha inicio.";
        }
        return $this;
    }

    public function numeric(string $value, string $field, string $message = ''): self
    {
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = $message ?: "El campo {$field} debe ser numérico.";
        }
        return $this;
    }

    public function range(string $value, float $min, float $max, string $field, string $message = ''): self
    {
        if ($value === '') {
            return $this;
        }
        $num = (float) $value;
        if ($num < $min || $num > $max) {
            $this->errors[$field] = $message ?: "El campo {$field} debe estar entre {$min} y {$max}.";
        }
        return $this;
    }

    public function alphaNumeric(string $value, string $field, string $message = ''): self
    {
        if ($value !== '' && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            $this->errors[$field] = $message ?: "El campo {$field} solo permite letras y números.";
        }
        return $this;
    }

    public function lengthBetween(string $value, int $min, int $max, string $field, string $message = ''): self
    {
        $len = mb_strlen($value);
        if ($len < $min || $len > $max) {
            $this->errors[$field] = $message ?: "El campo {$field} debe tener entre {$min} y {$max} caracteres.";
        }
        return $this;
    }
}
