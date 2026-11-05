<?php
    // Global system settings

    // Config.php is loaded by the front controller before the autoloader is
    // registered, so Env must be pulled in explicitly for the host resolution below.
    require_once __DIR__ . '/Env.php';

    /**
     * Resolves the host used to build BASE_URL.
     *
     * BASE_URL feeds every header('Location'), the asset <link> tags and the
     * password-recovery link sent by e-mail, so trusting $_SERVER['HTTP_HOST']
     * blindly lets an attacker inject an arbitrary origin (host header
     * poisoning). The host is therefore validated against ALLOWED_HOSTS.
     *
     * ALLOWED_HOSTS accepts a comma-separated list of hostnames and supports
     * the * wildcard for subdomains (e.g. "*.elomed.com.br").
     * When unset, localhost/127.0.0.1/::1 are accepted so local development
     * keeps working without extra configuration.
     */
    function elomed_resolver_host(): string {
        $fallback = 'localhost';
        $bruto    = $_SERVER['HTTP_HOST'] ?? '';

        // Host may carry an explicit port; drop it before matching.
        $host = trim($bruto);
        if ($host !== '' && strpos($host, ']') === false) {
            $host = preg_replace('/:\d+$/', '', $host);
        }
        $host = strtolower(trim((string) $host));

        if ($host === '') {
            return $fallback;
        }

        $permitidos = Env::get('ALLOWED_HOSTS', '');
        $permitidos = is_string($permitidos) ? $permitidos : '';
        $lista = array_filter(array_map('trim', explode(',', $permitidos)), 'strlen');

        if (!$lista) {
            $lista = ['localhost', '127.0.0.1', '::1'];
        }

        foreach ($lista as $permitido) {
            $permitido = strtolower($permitido);
            if (strpos($permitido, '*') !== false) {
                $regex = '/^' . str_replace('\*', '[^.]*', preg_quote($permitido, '/')) . '$/';
                if (preg_match($regex, $host)) {
                    return $host;
                }
            } elseif ($host === $permitido) {
                return $host;
            }
        }

        return $fallback;
    }

    // Project base URL. If the project moves to another folder, change it here.
    $__scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    // Keep the listening port so redirects and assets keep working when the
    // app is served on a non-standard port (e.g. Apache on 8081).
    $__port = (string) ($_SERVER['SERVER_PORT'] ?? '');
    if ($__port !== '' && $__port !== '80' && $__port !== '443') {
        $__port = ':' . $__port;
    } else {
        $__port = '';
    }
    define('BASE_URL', $__scheme . '://' . elomed_resolver_host() . $__port . '/elomed/public');
    define('APP_NAME', 'EloMed');

    // Global timezone: php.ini ships with Europe/Berlin on this deployment, but
    // the application (and the MySQL server) run in Brasília time. Setting it
    // here aligns NOW(), date() and the time_zone session for every request.
    if (function_exists('date_default_timezone_set')) {
        date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Sao_Paulo'));
    }

    /**
     * Central system configuration.
     * Reads values from .env (via Env) and returns sections with safe defaults.
     */
    class Config {
        private static $sections = null;

        /**
         * Builds the configuration sections with defaults.
         */
        private static function sections(): array {
            if (self::$sections === null) {
                self::$sections = [
                    'session' => [
                        'httponly' => true,
                        'secure'   => filter_var(Env::get('SESSION_SECURE', false), FILTER_VALIDATE_BOOLEAN),
                        'samesite' => Env::get('SESSION_SAMESITE', 'Strict'),
                        'lifetime' => (int) Env::get('SESSION_LIFETIME', 3600),
                        'name'     => Env::get('SESSION_NAME', 'elomed_session')
                    ],
                    'security' => [
                        'csrf_token_lifetime'       => (int) Env::get('CSRF_TOKEN_LIFETIME', 3600),
                        'password_min_length'       => (int) Env::get('PASSWORD_MIN_LENGTH', 8),
                        'password_require_uppercase'=> filter_var(Env::get('PASSWORD_REQUIRE_UPPER', true), FILTER_VALIDATE_BOOLEAN),
                        'password_require_number'   => filter_var(Env::get('PASSWORD_REQUIRE_NUMBER', true), FILTER_VALIDATE_BOOLEAN),
                        'password_require_special'  => filter_var(Env::get('PASSWORD_REQUIRE_SPECIAL', true), FILTER_VALIDATE_BOOLEAN)
                    ],
                    'cache' => [
                        'enabled'          => filter_var(Env::get('CACHE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
                        'prefix'           => Env::get('CACHE_PREFIX', 'elomed_'),
                        'default_lifetime' => (int) Env::get('CACHE_LIFETIME', 7200)
                    ],
                    'logging' => [
                        'path'      => Env::get('LOG_PATH', __DIR__ . '/../../logs'),
                        'filename'  => Env::get('LOG_FILENAME', 'app.log'),
                        // .env ships with LOGGING_ENABLED; accept LOG_ENABLED too
                        'enabled'   => filter_var(Env::get('LOGGING_ENABLED', Env::get('LOG_ENABLED', true)), FILTER_VALIDATE_BOOLEAN),
                        'level'     => Env::get('LOG_LEVEL', 'info'),
                        'max_files' => (int) Env::get('LOG_MAX_FILES', 30)
                    ]
                ];
            }
            return self::$sections;
        }

        /**
         * Gets a configuration value. Supports dots for sub-keys
         * (e.g. 'security.csrf_token_lifetime') and direct .env keys
         * (e.g. 'IA_PROVIDER').
         */
        public static function get(string $key, $default = null) {
            $directEnvKeys = ['IA_PROVIDER', 'GROQ_API_KEY', 'OPENAI_API_KEY'];
            if (in_array($key, $directEnvKeys, true)) {
                return Env::get($key, $default);
            }

            $current = self::sections();
            foreach (explode('.', $key) as $segment) {
                if (!is_array($current) || !array_key_exists($segment, $current)) {
                    return $default;
                }
                $current = $current[$segment];
            }
            return $current;
        }
    }