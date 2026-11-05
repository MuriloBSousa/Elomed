<?php

/**
 * Logging Helper
 * Records system events for auditing and debugging
 */

class Logger {
    private static ?Logger $instance = null;
    private string $logPath;
    private string $logFile;
    private array $config;
    
    private function __construct() {
        $this->config = Config::get('logging');
        $this->logPath = $this->config['path'] ?? __DIR__ . '/../../logs';
        $this->logFile = $this->logPath . '/' . ($this->config['filename'] ?? 'app.log');
        
        // Creates the log directory if it does not exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    public static function getInstance(): Logger {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Logs a debug message
     */
    public static function debug(string $message, array $context = []): void {
        self::getInstance()->log(Constants::LOG_LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Logs an info message
     */
    public static function info(string $message, array $context = []): void {
        self::getInstance()->log(Constants::LOG_LEVEL_INFO, $message, $context);
    }
    
    /**
     * Logs a warning message
     */
    public static function warning(string $message, array $context = []): void {
        self::getInstance()->log(Constants::LOG_LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Logs an error message
     */
    public static function error(string $message, array $context = []): void {
        self::getInstance()->log(Constants::LOG_LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Logs a critical message
     */
    public static function critical(string $message, array $context = []): void {
        self::getInstance()->log(Constants::LOG_LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Logs an audit action
     */
    public static function audit(string $action, array $context = []): void {
        $context['action'] = $action;
        $context['user_id'] = Session::get('usuario_id');
        $context['user_name'] = Session::get('usuario_nome');
        $context['user_perfil'] = Session::get('usuario_perfil');
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $context['timestamp'] = date('Y-m-d H:i:s');
        
        self::getInstance()->log(Constants::LOG_LEVEL_INFO, "AUDIT: {$action}", $context);
    }
    
    /**
     * Main logging method
     */
    private function log(string $level, string $message, array $context = []): void {
        if (!($this->config['enabled'] ?? true)) {
            return;
        }
        
        $minLevel = $this->config['level'] ?? Constants::LOG_LEVEL_INFO;
        $levels = [
            Constants::LOG_LEVEL_DEBUG => 0,
            Constants::LOG_LEVEL_INFO => 1,
            Constants::LOG_LEVEL_WARNING => 2,
            Constants::LOG_LEVEL_ERROR => 3,
            Constants::LOG_LEVEL_CRITICAL => 4
        ];
        
        if (($levels[$level] ?? 0) < ($levels[$minLevel] ?? 1)) {
            return;
        }
        
$timestamp = date('Y-m-d H:i:s');
        // Sanitizes message and context: line breaks in user-controlled input
        // would otherwise forge extra log lines (log injection).
        $message = trim(str_replace(["\r", "\n"], ' ', (string) $message));
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $contextStr = trim(str_replace(["\r", "\n"], ' ', $contextStr));

        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // LOCK_EX prevents concurrent requests from interleaving partial lines.
        @file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Rotates log files if necessary
        $this->rotateLogs();
    }
    
    /**
     * Rotates old log files
     */
    private function rotateLogs(): void {
        $maxFiles = $this->config['max_files'] ?? 30;
        
        if (!file_exists($this->logFile)) {
            return;
        }
        
        $fileSize = filesize($this->logFile);
        
        // Rotates if the file is larger than 10MB
        if ($fileSize > 10 * 1024 * 1024) {
            $timestamp = date('Y-m-d-His');
            $rotatedFile = $this->logPath . '/' . basename($this->logFile, '.log') . "_{$timestamp}.log";
            rename($this->logFile, $rotatedFile);
            
            // Removes old files
            $files = glob($this->logPath . '/*.log');
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            while (count($files) > $maxFiles) {
                unlink(array_shift($files));
            }
        }
    }
    
    /**
     * Clears all logs
     */
    public static function clear(): void {
        $instance = self::getInstance();
        $files = glob($instance->logPath . '/*.log');
        
        foreach ($files as $file) {
            unlink($file);
        }
    }
}