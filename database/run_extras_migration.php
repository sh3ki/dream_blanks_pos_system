<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../src/Core/Database.php';

$db = App\Core\Database::getInstance();

$db->query('ALTER TABLE project_lineups
  ADD COLUMN `link`  VARCHAR(500) NULL DEFAULT NULL AFTER `deadline`,
  ADD COLUMN `notes` TEXT         NULL DEFAULT NULL AFTER `link`,
  ADD COLUMN `photo` VARCHAR(255) NULL DEFAULT NULL AFTER `notes`');

echo 'Migration applied.' . PHP_EOL;

$cols = $db->select('SHOW COLUMNS FROM project_lineups WHERE Field IN ("link","notes","photo")');
foreach ($cols as $c) {
    echo $c['Field'] . ' - ' . $c['Type'] . PHP_EOL;
}
