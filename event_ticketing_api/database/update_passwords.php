<?php
require_once dirname(__DIR__) . '/api/config/Database.php';

$hash = password_hash('Password123!', PASSWORD_BCRYPT);
echo "Generated hash: " . $hash . "\n";
echo "Verify check: " . (password_verify('Password123!', $hash) ? 'TRUE' : 'FALSE') . "\n";

// Update in active MySQL / MariaDB database
$pdo = Database::getConnection();
$driver = Database::getDriver();
echo "Active database driver: $driver\n";

$stmt = $pdo->prepare("UPDATE users SET password_hash = ?");
$stmt->execute([$hash]);
echo "Updated " . $stmt->rowCount() . " users in $driver database.\n";

// Also update SQLite file directly if it exists
$sqliteFile = dirname(__DIR__) . '/database/EventTicketingDB.sqlite';
if (file_exists($sqliteFile)) {
    $sqlitePdo = new PDO("sqlite:" . $sqliteFile);
    $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt2 = $sqlitePdo->prepare("UPDATE users SET password_hash = ?");
    $stmt2->execute([$hash]);
    echo "Updated " . $stmt2->rowCount() . " users in SQLite database file.\n";
}

// Also update schema.sql and sqlite_schema.sql so future imports use this exact hash
$schemaFile = dirname(__DIR__) . '/database/schema.sql';
if (file_exists($schemaFile)) {
    $content = file_get_contents($schemaFile);
    $content = preg_replace('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $hash, $content);
    file_put_contents($schemaFile, $content);
    echo "Updated schema.sql with fresh bcrypt hash.\n";
}

$sqliteSchemaFile = dirname(__DIR__) . '/database/sqlite_schema.sql';
if (file_exists($sqliteSchemaFile)) {
    $content = file_get_contents($sqliteSchemaFile);
    $content = preg_replace('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $hash, $content);
    file_put_contents($sqliteSchemaFile, $content);
    echo "Updated sqlite_schema.sql with fresh bcrypt hash.\n";
}
