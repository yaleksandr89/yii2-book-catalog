<?php

declare(strict_types=1);

use yii\db\Connection;

$mainDatabase = getenv('MYSQL_DATABASE') ?: 'yii2_book_catalog';
$testDatabase = getenv('MYSQL_TEST_DATABASE');
if ($testDatabase === false) {
    $testDatabase = 'yii2_book_catalog_test';
}

if (
    preg_match('/\A[A-Za-z0-9_]{1,64}\z/', $testDatabase) !== 1
    || in_array(strtolower($testDatabase), ['mysql', 'information_schema', 'performance_schema', 'sys'], true)
) {
    throw new RuntimeException('MYSQL_TEST_DATABASE must be a safe, non-system MySQL schema name.');
}
if (preg_match('/\A[A-Za-z0-9_-]{1,64}\z/', $mainDatabase) !== 1) {
    throw new RuntimeException('MYSQL_DATABASE must specify an unambiguous application schema for testing.');
}
if (strcasecmp($mainDatabase, $testDatabase) === 0) {
    throw new RuntimeException('MYSQL_TEST_DATABASE must differ from the effective application database.');
}

$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: '3306';
if ($host !== 'mysql' || $port !== '3306') {
    throw new RuntimeException('Test targets require the local Compose MySQL endpoint (mysql:3306).');
}

return [
    'class' => Connection::class,
    'dsn' => 'mysql:host=mysql;port=3306;dbname=' . $testDatabase,
    'username' => getenv('MYSQL_USER') ?: 'yii2',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
