<?php

declare(strict_types=1);

return [
    'class' => \yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        getenv('DB_HOST') ?: 'mysql',
        getenv('DB_PORT') ?: '3306',
        getenv('MYSQL_DATABASE') ?: 'yii2_book_catalog',
    ),
    'username' => getenv('MYSQL_USER') ?: 'yii2',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
    'charset' => 'utf8mb4',

    // RU: Параметры кэширования схемы для production.
    // EN: Schema cache options for production.
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
