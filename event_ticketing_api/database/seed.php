<?php
// =====================================================================
// Automated Database Initialization & Seeding Script
// Can seed either SQLite (default) or MySQL/MariaDB
// =====================================================================

$dbDir = __DIR__;
$sqliteFile = $dbDir . DIRECTORY_SEPARATOR . 'EventTicketingDB.sqlite';
$sqliteSql = file_get_contents($dbDir . DIRECTORY_SEPARATOR . 'sqlite_schema.sql');

echo "Initializing SQLite database at: $sqliteFile\n";

try {
    if (file_exists($sqliteFile)) {
        unlink($sqliteFile);
    }
    $pdo = new PDO("sqlite:$sqliteFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec($sqliteSql);
    echo "[SUCCESS] SQLite database successfully created and populated with sample data!\n";
} catch (Exception $e) {
    echo "[ERROR] SQLite initialization failed: " . $e->getMessage() . "\n";
}

// Check if MariaDB on port 3307 or 3306 is reachable and seed it too
$mysqlHosts = [
    ['host' => '127.0.0.1', 'port' => 3307, 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => '']
];

foreach ($mysqlHosts as $m) {
    try {
        $pdoMy = new PDO("mysql:host={$m['host']};port={$m['port']}", $m['user'], $m['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Found active MySQL/MariaDB at {$m['host']}:{$m['port']}. Seeding MySQL database...\n";
        $mysqlSql = file_get_contents($dbDir . DIRECTORY_SEPARATOR . 'schema.sql');
        $pdoMy->exec($mysqlSql);
        echo "[SUCCESS] MySQL database 'event_ticketing_db' created and seeded successfully on port {$m['port']}!\n";
        break;
    } catch (Exception $e) {
        // Continue if port is unavailable
    }
}
echo "Database seeding process complete.\n";

