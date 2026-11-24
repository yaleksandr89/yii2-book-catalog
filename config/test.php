<?php

declare(strict_types=1);

if (getenv('YII2_TEST_DB_GUARD') !== '1') {
    throw new RuntimeException('Run integration tests through a guarded Make target.');
}

$testDb = require __DIR__ . '/test_db.php';
$config = require __DIR__ . '/web.php';

$config['id'] = 'basic-test';
$config['components']['db'] = $testDb;
$config['components']['request']['cookieValidationKey'] = 'test-cookie-validation-key';
$config['components']['assetManager'] = [
    'basePath' => '@app/runtime/test-assets',
    'baseUrl' => '/assets',
];
$config['params']['bookImageStorageRoot'] = '@runtime/test-book-uploads';
$config['params']['smsPilotApiKey'] = 'test-sms-pilot-api-key';

return $config;
