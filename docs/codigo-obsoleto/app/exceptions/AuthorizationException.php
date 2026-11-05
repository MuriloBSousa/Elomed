<?php

/**
 * Authorization exception
 * Thrown when the user has no permission to perform an action
 */

class AuthorizationException extends AppException {
    public function __construct(string $message = "Permissão negada", array $context = []) {
        parent::__construct($message, $context, 403);
    }
}