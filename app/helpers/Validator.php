<?php

/**
 * Input validation helper
 * Validates and sanitizes input data
 */

class Validator {
    private array $errors = [];
    private array $data = [];
    
    public function __construct(array $data = []) {
        $this->data = $data;
    }
    
    /**
     * Validates a field as required
     */
    public function required(string $field, string $message = null): self {
        if (!isset($this->data[$field]) || empty($this->data[$field])) {
            $this->errors[$field] = $message ?? "O campo {$field} é obrigatório";
        }
        return $this;
    }
    
    /**
     * Validates a field as email
     */
    public function email(string $field, string $message = null): self {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = $message ?? "O campo {$field} deve ser um email válido";
            }
        }
        return $this;
    }
    
    /**
     * Validates the minimum length of a field
     */
    public function min(string $field, int $min, string $message = null): self {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "O campo {$field} deve ter no mínimo {$min} caracteres";
        }
        return $this;
    }
    
    /**
     * Validates the maximum length of a field
     */
    public function max(string $field, int $max, string $message = null): self {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "O campo {$field} deve ter no máximo {$max} caracteres";
        }
        return $this;
    }
    
    /**
     * Validates whether a field is numeric
     */
    public function numeric(string $field, string $message = null): self {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field] = $message ?? "O campo {$field} deve ser numérico";
            }
        }
        return $this;
    }
    
    /**
     * Validates whether a field is an integer
     */
    public function integer(string $field, string $message = null): self {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
                $this->errors[$field] = $message ?? "O campo {$field} deve ser um inteiro";
            }
        }
        return $this;
    }
    
    /**
     * Validates whether a field is in a list of allowed values
     */
    public function in(string $field, array $allowed, string $message = null): self {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            if (!in_array($this->data[$field], $allowed)) {
                $this->errors[$field] = $message ?? "O campo {$field} deve ser um dos valores permitidos";
            }
        }
        return $this;
    }
    
    /**
     * Validates whether a field matches another field
     */
    public function matches(string $field, string $otherField, string $message = null): self {
        if (isset($this->data[$field]) && isset($this->data[$otherField])) {
            if ($this->data[$field] !== $this->data[$otherField]) {
                $this->errors[$field] = $message ?? "O campo {$field} deve corresponder ao campo {$otherField}";
            }
        }
        return $this;
    }
    
    /**
     * Validates a password
     */
    public function password(string $field, string $message = null): self {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $password = $this->data[$field];
            $config = Config::get('security');
            
            if (strlen($password) < ($config['password_min_length'] ?? 8)) {
                $this->errors[$field] = $message ?? "A senha deve ter no mínimo {$config['password_min_length']} caracteres";
                return $this;
            }
            
            if (($config['password_require_uppercase'] ?? true) && !preg_match('/[A-Z]/', $password)) {
                $this->errors[$field] = $message ?? "A senha deve conter pelo menos uma letra maiúscula";
                return $this;
            }
            
            if (($config['password_require_number'] ?? true) && !preg_match('/[0-9]/', $password)) {
                $this->errors[$field] = $message ?? "A senha deve conter pelo menos um número";
                return $this;
            }
            
            if (($config['password_require_special'] ?? true) && !preg_match('/[^a-zA-Z0-9]/', $password)) {
                $this->errors[$field] = $message ?? "A senha deve conter pelo menos um caractere especial";
                return $this;
            }
        }
        return $this;
    }
    
    /**
     * Validates a date
     */
    public function date(string $field, string $format = 'Y-m-d', string $message = null): self {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "O campo {$field} deve ser uma data válida no formato {$format}";
            }
        }
        return $this;
    }
    
    /**
     * Sanitizes a field
     */
    public function sanitize(string $field): string {
        if (isset($this->data[$field])) {
            $this->data[$field] = htmlspecialchars(strip_tags(trim($this->data[$field])), ENT_QUOTES, 'UTF-8');
        }
        return $this->data[$field] ?? '';
    }
    
    /**
     * Sanitizes all fields
     */
    public function sanitizeAll(): array {
        foreach ($this->data as $key => $value) {
            if (is_string($value)) {
                $this->data[$key] = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
            }
        }
        return $this->data;
    }
    
    /**
     * Checks whether the validation passed
     */
    public function passes(): bool {
        return empty($this->errors);
    }
    
    /**
     * Checks whether the validation failed
     */
    public function fails(): bool {
        return !$this->passes();
    }
    
    /**
     * Gets the validation errors
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Gets the first validation error
     */
    public function getFirstError(): string {
        return reset($this->errors) ?? '';
    }
    
    /**
     * Gets the validated data
     */
    public function getData(): array {
        return $this->data;
    }
    
    /**
     * Sets the data to validate
     */
    public function setData(array $data): self {
        $this->data = $data;
        return $this;
    }

    /**
     * Validates a CPF (Brazilian taxpayer ID): length, repeated digits and
     * both check digits. Static so non-validator flows can reuse the check.
     */
    public static function isValidCpf(string $valor): bool {
        $cpf = preg_replace('/\D/', '', $valor);
        if (strlen($cpf) !== 11) {
            return false;
        }
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digit = (($sum * 10) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validates a CPF (Brazilian taxpayer ID)
     */
    public function cpf(string $field, string $message = null): self {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $cpf = preg_replace('/\D/', '', $this->data[$field]);
            if (strlen($cpf) !== 11) {
                $this->errors[$field] = $message ?? "O campo {$field} deve ter 11 dígitos";
                return $this;
            }
            if (!self::isValidCpf($cpf)) {
                $this->errors[$field] = $message ?? "O campo {$field} é inválido";
                return $this;
            }
        }
        return $this;
    }

    /**
     * Validates a birth date
     */
    public function dateNascimento(string $field, string $message = null): self {
        if (isset($this->data[$field]) && !empty($this->data[$field])) {
            $ts = strtotime($this->data[$field]);
            if (!$ts || $this->data[$field] !== date('Y-m-d', $ts)) {
                $this->errors[$field] = $message ?? "O campo {$field} deve ser uma data válida";
                return $this;
            }
            if ($ts > strtotime('today')) {
                $this->errors[$field] = $message ?? "A {$field} não pode estar no futuro";
                return $this;
            }
            if ((int) date('Y', $ts) < 1900) {
                $this->errors[$field] = $message ?? "A {$field} é muito antiga (antes de 1900)";
                return $this;
            }
        }
        return $this;
    }
}