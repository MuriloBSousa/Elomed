<?php

/**
 * Authentication exception
 * Thrown when there are authentication errors
 */

class AuthenticationException extends AppException {
    public function __construct(string $message = "Falha na autenticação", array $context = []) {
        parent::__construct($message, $context, 401);
    }
}