<?php
class Database {
    private static $instance = null;

    private static function loadEnv() {
        static $envLoaded = false;
        if (!$envLoaded && file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env');
            foreach ($lines as $line) {
                [$key, $value] = explode('=', trim($line), 2);
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
            $envLoaded = true;
        }
    }

    public static function getConnection() {
        self::loadEnv(); 

        if (self::$instance === null) {
            error_log("Creating a new database connection.");
            $host = getenv('DB_HOST');
            $user = getenv('DB_USER');
            $pass = getenv('DB_PASS');
            $name = getenv('DB_NAME');
            $port = getenv('DB_PORT');

            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try {
                self::$instance = new mysqli($host, $user, $pass, $name, $port);
            } catch (mysqli_sql_exception $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
?>
