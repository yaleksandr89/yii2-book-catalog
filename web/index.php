<?php

declare(strict_types=1);

// RU: При развёртывании в production закомментируйте следующие две строки.
// EN: Comment out the next two lines when deploying to production.
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

new yii\web\Application($config)->run();
