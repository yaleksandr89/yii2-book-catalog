<?php

declare(strict_types=1);

if (YII_ENV_TEST && getenv('YII2_TEST_DB_GUARD') !== '1') {
    throw new RuntimeException('Run test migrations through make test-db-migrate.');
}

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . (YII_ENV_TEST ? '/test_db.php' : '/db.php');

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        // RU: Команда генерации фикстур.
        // EN: Fixture generation command.
        'fixture' => [
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
    // RU: Для отладчика требуется yii2-debug версии 2.1.21 или новее.
    // EN: The debugger requires yii2-debug 2.1.21 or later.
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // RU: Для подключения не с localhost раскомментируйте строку и добавьте свой IP.
        // EN: For connections from outside localhost, uncomment the line and add your IP.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
