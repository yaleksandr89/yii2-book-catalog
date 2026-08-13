<?php

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

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
