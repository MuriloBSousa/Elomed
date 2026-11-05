<?php

/**
 * Loads environment variables from the .env file
 */

class Env {
    private static bool $loaded = false;
    
    /**
     * Loads the .env file and sets the environment variables
     */
    public static function load(string $path = null): void {
        if (self::$loaded) {
            return;
        }
        
        $path = $path ?? __DIR__ . '/../../.env';
        
        if (!file_exists($path)) {
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skips comments (including full-line and leading whitespace comments)
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parses the line
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Strips a trailing inline comment when the value is not quoted
                if (strpos($value, '#') !== false) {
                    $firstQuote = strpos($value, '"');
                    if (!(($value[0] === '"' || $value[0] === "'") && $firstQuote === 0)) {
                        $value = trim(substr($value, 0, strpos($value, '#')));
                    }
                }

                // Removes surrounding quotes only when both ends are the same pair
                $len = strlen($value);
                if ($len >= 2 && $value !== '""' && $value !== "''") {
                    $first = $value[0];
                    $last  = $value[$len - 1];
                    if (($first === '"' || $first === "'") && $first === $last) {
                        $value = substr($value, 1, -1);
                    }
                }

                // Empty value is a valid explicit value; never fall back when set.
                if (getenv($name) === false) {
                    putenv($name . '=' . $value);
                    $_ENV[$name] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Gets an environment variable.
     * Uses ?? so an empty or "0" value is honored instead of falling back to
     * the default (getenv returns false only when the variable is not set).
     */
    public static function get(string $key, $default = null) {
        self::load();
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}