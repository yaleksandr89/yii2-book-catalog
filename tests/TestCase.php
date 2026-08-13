<?php

declare(strict_types=1);

namespace tests;

use Yii;
use yii\helpers\FileHelper;
use yii\web\Application;
use yii\web\Request;
use yii\web\UploadedFile;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(require dirname(__DIR__) . '/config/test.php');
        Yii::$app = $this->app;
        FileHelper::createDirectory(dirname(__DIR__) . '/runtime/test-assets');
        FileHelper::createDirectory(Yii::getAlias('@runtime/test-book-uploads'));
        $this->setRequestMethod('GET');
    }

    protected function tearDown(): void
    {
        $this->app->db->createCommand()->delete('{{%book_author}}')->execute();
        $this->app->db->createCommand()->delete('{{%book}}')->execute();
        $this->app->db->createCommand()->delete('{{%author}}')->execute();
        $this->app->db->createCommand()->delete('{{%user}}')->execute();
        FileHelper::removeDirectory(Yii::getAlias('@runtime/test-book-uploads'));
        UploadedFile::reset();
        $this->app->user->logout(false);
        $this->app->db->close();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    protected function setRequestMethod(string $method): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/web/index.php';
        $this->app->set('request', new Request([
            'enableCsrfValidation' => false,
            'cookieValidationKey' => 'test-cookie-validation-key',
            'scriptFile' => dirname(__DIR__) . '/web/index.php',
            'scriptUrl' => '/index.php',
        ]));
    }
}
