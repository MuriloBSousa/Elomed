<?php

/**
 * Validation exception
 * Thrown when there are data validation errors
 */

class ValidationException extends AppException {
    private array $errors = [];
    
    public function __construct(string $message, array $errors = [], array $context = []) {
        $this->errors = $errors;
        parent::__construct($message, $context, 422);
    }
    
    public function getErrors(): array {
        return $this->errors;
    }
}