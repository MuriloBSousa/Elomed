<?php

/**
 * Cache Helper
 * Implements a simple file-based cache system
 */

class Cache {
    private static ?Cache $instance = null;
    private string $cachePath;
    private string $prefix;
    private int $defaultLifetime;
    private bool $enabled;
    
    private function __construct() {
        $config = Config::get('cache');
        $this->enabled = $config['enabled'] ?? false;
        $this->cachePath = __DIR__ . '/../../cache';
        $this->prefix = $config['prefix'] ?? 'elomed_';
        $this->defaultLifetime = $config['default_lifetime'] ?? Constants::CACHE_LIFETIME_MEDIUM;
        
        // Creates the cache directory if it does not exist
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public static function getInstance(): Cache {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Stores a value in the cache
     */
    public static function set(string $key, $value, int $lifetime = null): bool {
        if (!self::getInstance()->enabled) {
            return false;
        }
        
        $instance = self::getInstance();
        $lifetime = $lifetime ?? $instance->defaultLifetime;
        $filename = $instance->getFilename($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + $lifetime
        ];
        
        return file_put_contents($filename, serialize($data), LOCK_EX) !== false;
    }
    
    /**
     * Gets a value from the cache
     */
    public static function get(string $key, $default = null) {
        if (!self::getInstance()->enabled) {
            return $default;
        }
        
        $instance = self::getInstance();
        $filename = $instance->getFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $data = self::readCacheFile($filename);
        if ($data === null) {
            unlink($filename);
            return $default;
        }
        
        // Checks if it has expired
        if ($data['expires'] < time()) {
            unlink($filename);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Checks whether a key exists in the cache
     */
    public static function has(string $key): bool {
        if (!self::getInstance()->enabled) {
            return false;
        }
        
        $instance = self::getInstance();
        $filename = $instance->getFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        $data = self::readCacheFile($filename);
        if ($data === null) {
            unlink($filename);
            return false;
        }
        
        // Checks if it has expired
        if ($data['expires'] < time()) {
            unlink($filename);
            return false;
        }
        
        return true;
    }
    
    /**
     * Removes a value from the cache
     */
    public static function forget(string $key): bool {
        $instance = self::getInstance();
        $filename = $instance->getFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Reads a cache file safely.
     *
     * The cache only ever stores scalars and arrays, so object instantiation is
     * disabled: a tampered cache file yields __PHP_Incomplete_Class instead of
     * running attacker-controlled magic methods. Returns null for missing,
     * unreadable, corrupt or foreign payloads so callers can drop the file.
     */
    private static function readCacheFile(string $filename): ?array {
        if (!is_file($filename) || !is_readable($filename)) {
            return null;
        }

        $raw = @file_get_contents($filename);
        if ($raw === false || $raw === '') {
            return null;
        }

        $data = @unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($data) || !isset($data['expires']) || !array_key_exists('value', $data)) {
            return null;
        }

        return $data;
    }
    
    /**
     * Clears the entire cache
     */
    public static function clear(): bool {
        $instance = self::getInstance();
        $files = glob($instance->cachePath . '/' . $instance->prefix . '*');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Gets the file name for a key
     */
    private function getFilename(string $key): string {
        $safeKey = md5($key);
        return $this->cachePath . '/' . $this->prefix . $safeKey . '.cache';
    }
    
    /**
     * Removes expired items from the cache
     */
    public static function cleanup(): int {
        $instance = self::getInstance();
        $files = glob($instance->cachePath . '/' . $instance->prefix . '*');
        $removed = 0;
        
        foreach ($files as $file) {
            $data = self::readCacheFile($file);
            
            if ($data === null || $data['expires'] < time()) {
                unlink($file);
                $removed++;
            }
        }
        
        return $removed;
    }
    
    /**
     * Remembers a value for a specific time (simple cache)
     */
    public static function remember(string $key, int $lifetime, callable $callback) {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        self::set($key, $value, $lifetime);
        
        return $value;
    }
}