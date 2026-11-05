<?php

/**
 * Not found exception
 * Thrown when a resource is not found
 */

class NotFoundException extends AppException {
    public function __construct(string $message = "Recurso não encontrado", array $context = []) {
        parent::__construct($message, $context, 404);
    }
}