<?php

/**
 * Session security middleware
 * Manages the session security settings
 */

class Session {
    private static bool $started = false;
    
    /**
     * Starts the session with security settings
     */
    public static function start(): void {
        if (self::$started) {
            return;
        }

        // If the session was already started (e.g. by the front controller),
        // just marks it as started and returns.
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $config = Config::get('session');
        
        // Session security settings
        ini_set('session.cookie_httponly', $config['httponly'] ?? true);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', $config['secure'] ?? false);
        ini_set('session.cookie_samesite', $config['samesite'] ?? 'Strict');
        ini_set('session.gc_maxlifetime', $config['lifetime'] ?? 3600);
        
        // Custom session name
        if (!empty($config['name'])) {
            session_name($config['name']);
        }
        
        session_start();
        
        // Periodically regenerates the session ID to prevent fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        self::$started = true;
    }
    
    /**
     * Destroys the session
     */
    public static function destroy(): void {
        if (!self::$started) {
            return;
        }
        
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        self::$started = false;
    }
    
    /**
     * Sets a value in the session
     */
    public static function set(string $key, $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Gets a value from the session
     */
    public static function get(string $key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Checks whether a key exists in the session
     */
    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Removes a value from the session
     */
    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Sets a flash message (displayed once and then removed)
     */
    public static function flash(string $key, $value): void {
        self::set('_flash_' . $key, $value);
    }
    
    /**
     * Gets and removes a flash message
     */
    public static function getFlash(string $key, $default = null) {
        $value = self::get('_flash_' . $key, $default);
        self::remove('_flash_' . $key);
        return $value;
    }
    
    /**
     * Checks whether there is a flash message
     */
    public static function hasFlash(string $key): bool {
        return self::has('_flash_' . $key);
    }

    /**
     * Le a mensagem de flash SEM remover da sessao.
     *
     * POR QUE EXISTE: o getFlash() apaga a mensagem quando le. O
     * renderizador de toasts precisa ler para montar o toast e so
     * depois apagar - se usasse o getFlash(), a sessao ja ficaria
     * limpa e ainda seria preciso outro metodo para apagar.
     */
    public static function peekFlash(string $key, $default = null) {
        return self::get('_flash_' . $key, $default);
    }

    /**
     * Remove a mensagem de flash SEM ler (usado depois de ja ter lido).
     */
    public static function forgetFlash(string $key): void {
        self::remove('_flash_' . $key);
    }

    /**
     * Devolve as mensagens de flash como uma lista pronta para o toasts.js.
     * Nao consome a sessao: quem consome e o metodo limparFlash().
     */
    public static function toastsFlash(): array {
        $fila = [];
        foreach (['success', 'error', 'warning', 'info'] as $chave) {
            $mensagem = self::peekFlash($chave);
            if (is_string($mensagem) && trim($mensagem) !== '') {
                $fila[] = [
                    'tipo'     => $chave,
                    'mensagem' => $mensagem,
                ];
            }
        }
        return $fila;
    }

    /**
     * Apaga as mensagens de flash (depois que o toast ja foi montado).
     */
    public static function limparFlash(): void {
        foreach (['success', 'error', 'warning', 'info'] as $chave) {
            self::forgetFlash($chave);
        }
    }

    /**
     * Gets the session ID
     */
    public static function getId(): string {
        self::start();
        return session_id();
    }
    
    /**
     * Checks whether the session is active
     */
    public static function isActive(): bool {
        return self::$started && session_status() === PHP_SESSION_ACTIVE;
    }
}