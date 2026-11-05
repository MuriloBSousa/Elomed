<?php

/**
 * CSRF protection middleware
 * Generates and validates CSRF tokens to prevent attacks
 */

class CSRF {
    private static string $tokenName = 'csrf_token';
    private static ?string $token = null;
    
    /**
     * Generates a new CSRF token
     */
    public static function generateToken(): string {
        if (self::$token === null) {
            self::$token = bin2hex(random_bytes(32));
            $_SESSION[self::$tokenName] = self::$token;
            $_SESSION[self::$tokenName . '_time'] = time();
        }
        return self::$token;
    }
    
    /**
     * Gets the current CSRF token
     */
    public static function getToken(): string {
        if (self::$token === null) {
            self::$token = $_SESSION[self::$tokenName] ?? self::generateToken();
        }
        return self::$token;
    }
    
    /**
     * Validates a CSRF token
     */
    public static function validateToken(string $token): bool {
        if (!isset($_SESSION[self::$tokenName])) {
            return false;
        }
        
        $sessionToken = $_SESSION[self::$tokenName];
        $tokenTime = $_SESSION[self::$tokenName . '_time'] ?? 0;
        $lifetime = Config::get('security.csrf_token_lifetime', 3600);
        
        // Checks if the token has expired
        if (time() - $tokenTime > $lifetime) {
            self::regenerateToken();
            return false;
        }
        
        // Checks if the token matches
        if (hash_equals($sessionToken, $token)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Regenerates the CSRF token
     */
    public static function regenerateToken(): void {
        self::$token = null;
        unset($_SESSION[self::$tokenName]);
        unset($_SESSION[self::$tokenName . '_time']);
    }
    
    /**
     * Gets the CSRF token HTML field
     */
    public static function getField(): string {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validates the POST request token
     */
    public static function validateRequest(): bool {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }
        
        $token = $_POST[self::$tokenName] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token)) {
            return false;
        }
        
        return self::validateToken($token);
    }
}