<?php
// =====================================================================
// Database Connection Manager with Dual Engine Support (MySQL / SQLite)
// =====================================================================

class Database {
    private static ?PDO $connection = null;
    private static string $driver = '';

    public static function getConnection(): PDO {
        if (self::$connection !== null) {
            return self::$connection;
        }

        // Try MySQL/MariaDB on port 3307 or 3306
        $mysqlConfigs = [
            ['host' => '127.0.0.1', 'port' => 3307, 'dbname' => 'event_ticketing_db', 'user' => 'root', 'pass' => ''],
            ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'event_ticketing_db', 'user' => 'root', 'pass' => '']
        ];

        foreach ($mysqlConfigs as $cfg) {
            try {
                $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset=utf8mb4";
                $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 2
                ]);
                self::$connection = $pdo;
                self::$driver = 'mysql';
                return self::$connection;
            } catch (PDOException $e) {
                // Try next configuration
            }
        }

        // Seamless Fallback to SQLite
        $sqlitePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'EventTicketingDB.sqlite';
        try {
            $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            self::$connection = $pdo;
            self::$driver = 'sqlite';
            return self::$connection;
        } catch (PDOException $e) {
            throw new Exception("Database Connection Error: Unable to connect to MySQL or SQLite. " . $e->getMessage());
        }
    }

    public static function getDriver(): string {
        if (self::$connection === null) {
            self::getConnection();
        }
        return self::$driver;
    }
}
