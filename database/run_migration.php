<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';

$db = App\Core\Database::getInstance();

// Verify
$rows = $db->query("SELECT module, action, description FROM permissions WHERE module IN ('project_lineup') OR (module='invoices' AND action='forward') ORDER BY module, action")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['module'] . '.' . $r['action'] . ' => ' . $r['description'] . PHP_EOL;
}
echo 'Table exists: ';
$t = $db->query("SHOW TABLES LIKE 'project_lineups'")->fetchAll();
echo (count($t) ? 'YES' : 'NO') . PHP_EOL;
