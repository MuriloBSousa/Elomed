<?php

/**
 * Database exception
 * Thrown when there are database errors
 */

class DatabaseException extends AppException {
    private ?string $sql = null;
    
    public function __construct(string $message, ?string $sql = null, array $context = []) {
        $this->sql = $sql;
        parent::__construct($message, $context, 500);
    }
    
    public function getSql(): ?string {
        return $this->sql;
    }
}