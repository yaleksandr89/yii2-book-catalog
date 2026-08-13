<?php

declare(strict_types=1);

$config = require __DIR__ . '/web.php';

$config['id'] = 'basic-test';
$config['components']['db'] = require __DIR__ . '/test_db.php';
$config['components']['request']['cookieValidationKey'] = 'test-cookie-validation-key';
$config['components']['assetManager'] = [
    'basePath' => '@app/runtime/test-assets',
    'baseUrl' => '/assets',
];

return $config;
