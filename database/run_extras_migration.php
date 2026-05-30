<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';

$file = $argv[1] ?? null;
if (!$file || !file_exists(__DIR__ . '/../' . $file)) {
    die('Usage: php run_extras_migration.php <relative-sql-file>' . PHP_EOL);
}

$pdo = App\Core\Database::getInstance()->getPdo();
$sql = file_get_contents(__DIR__ . '/../' . $file);

// Split and run each statement
foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    $pdo->exec($stmt);
}

echo 'Migration applied: ' . $file . PHP_EOL;
