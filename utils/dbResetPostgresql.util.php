<?php

declare(strict_types=1);

// 1) Composer autoload
require 'vendor/autoload.php';

// 2) Composer bootstrap
require 'bootstrap.php';

// 3) envSetter
require_once UTILS_PATH . '/envSetter.util.php';

// ——— Connect to PostgreSQL ———
$dsn = "pgsql:host={$pgConfig['host']};port={$pgConfig['port']};dbname={$pgConfig['db']}";
$pdo = new PDO($dsn, $pgConfig['user'], $pgConfig['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Just indicator it was working
echo "Applying schema from database/user.model.sql…\n";

$sql = file_get_contents('database/user.model.sql');

// Another indicator but for failed creation
if ($sql === false) {
  throw new RuntimeException("Could not read database/user.model.sql");
} else {
    echo "Creation Success from the database/user.model.sql";
}

// If your model.sql contains a working command it will be executed
$pdo->exec($sql);

echo "Truncating tables…\n";
foreach (['users'] as $table) {
  $pdo->exec("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE;");
}

?>