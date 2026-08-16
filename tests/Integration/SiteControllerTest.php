<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\models\User;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yii;
use yii\web\MethodNotAllowedHttpException;

final class SiteControllerTest extends TestCase
{
    #[TestDox('Гость открывает главную страницу с техническим заданием')]
    public function testGuestCanOpenProjectHomepage(): void
    {
        $html = $this->app->runAction('site/index');

        self::assertIsString($html);
        self::assertStringContainsString('Каталог книг на Yii2', $html);
        self::assertStringContainsString('ТОП-10', $html);
        self::assertStringContainsString('SMSPilot', $html);
        self::assertStringContainsString('Web, не API', $html);
        self::assertStringNotContainsString('Build with Yii Framework', $html);
        self::assertStringNotContainsString('yii2-queue', $html);
        self::assertStringNotContainsString('yii2-elasticsearch', $html);
    }

    #[TestDox('Гость открывает страницу входа')]
    public function testGuestCanOpenLoginPage(): void
    {
        $html = $this->app->runAction('site/login');

        self::assertIsString($html);
        self::assertStringContainsString('login-form', $html);
    }

    #[TestDox('Корректный POST вход аутентифицирует пользователя и перенаправляет его')]
    public function testValidLoginPostAuthenticatesAndRedirects(): void
    {
        $user = $this->createUser('site-reader', 'correct-password');
        $_POST = ['LoginForm' => [
            'username' => $user->username,
            'password' => 'correct-password',
            'rememberMe' => '1',
        ]];
        $this->setRequestMethod('POST');

        $this->app->runAction('site/login');

        self::assertSame(302, $this->app->response->statusCode);
        self::assertSame($user->id, Yii::$app->user->id);
    }

    #[TestDox('Аутентифицированный пользователь перенаправляется со страницы входа на главную')]
    public function testAuthenticatedUserIsRedirectedFromLogin(): void
    {
        $user = $this->createUser('signed-in-reader', 'correct-password');
        self::assertTrue(Yii::$app->user->login($user));

        $this->app->runAction('site/login');

        self::assertSame(302, $this->app->response->statusCode);
        self::assertSame($user->id, Yii::$app->user->id);
    }

    #[TestDox('Выход отклоняет GET-запрос аутентифицированного пользователя')]
    public function testLogoutRejectsGetRequest(): void
    {
        $user = $this->createUser('logout-get-reader', 'correct-password');
        self::assertTrue(Yii::$app->user->login($user));

        $this->expectException(MethodNotAllowedHttpException::class);
        $this->app->runAction('site/logout');
    }

    #[TestDox('POST выход возвращает аутентифицированного пользователя в гостевое состояние')]
    public function testAuthenticatedPostLogoutReturnsUserToGuestState(): void
    {
        $user = $this->createUser('logout-post-reader', 'correct-password');
        self::assertTrue(Yii::$app->user->login($user));
        $this->setRequestMethod('POST');

        $this->app->runAction('site/logout');

        self::assertTrue(Yii::$app->user->isGuest);
        self::assertSame(302, $this->app->response->statusCode);
    }

    private function createUser(string $username, string $password): User
    {
        $user = new User([
            'username' => $username,
            'password_hash' => Yii::$app->security->generatePasswordHash($password),
            'auth_key' => Yii::$app->security->generateRandomString(),
        ]);
        self::assertTrue($user->save());

        return $user;
    }
}
