<?php
class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            // Credentials read from .env (never hardcoded)
            $host   = Env::get('DB_HOST', 'localhost');
            $dbname = Env::get('DB_NAME', 'elomed');
            $port   = Env::get('DB_PORT', '3306');
            $user   = Env::get('DB_USER', 'root');
            $pass   = Env::get('DB_PASS', '');

            try {
                // The MySQL session time_zone is pinned to PHP's current offset
                // (e.g. -03:00 for Brasília) so NOW()/CURDATE() agree with the
                // application clock, regardless of the server's SYSTEM tz.
                $tzOffset = date('P');
                self::$connection = new PDO(
                    "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $dbname . ";charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . $tzOffset . "'"
                    ]
                );
            } catch (PDOException $e) {
                error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
                http_response_code(500);
                die("Erro na conexão com o banco de dados.");
            }
        }
        return self::$connection;
    }
}