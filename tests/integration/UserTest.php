<?php

declare(strict_types=1);

namespace tests\integration;

use app\models\LoginForm;
use app\models\User;
use PHPUnit\Framework\Attributes\TestDox;
use tests\TestCase;
use Yii;

final class UserTest extends TestCase
{
    #[TestDox('Сохраненный пользователь проверяет верный пароль')]
    public function testPersistedUserValidatesCorrectPassword(): void
    {
        $user = $this->createUser('reader', 'correct-password');

        self::assertTrue($user->validatePassword('correct-password'));
        self::assertFalse($user->validatePassword('wrong-password'));
    }

    #[TestDox('Верные учетные данные аутентифицируют пользователя')]
    public function testValidCredentialsAuthenticate(): void
    {
        $user = $this->createUser('reader', 'correct-password');
        $form = new LoginForm([
            'username' => $user->username,
            'password' => 'correct-password',
        ]);

        self::assertTrue($form->login());
        self::assertSame($user->id, Yii::$app->user->id);
    }

    #[TestDox('Неверные учетные данные отклоняются')]
    public function testInvalidCredentialsAreRejected(): void
    {
        $this->createUser('reader', 'correct-password');
        $form = new LoginForm([
            'username' => 'reader',
            'password' => 'wrong-password',
        ]);

        self::assertFalse($form->login());
        self::assertSame(['Неверное имя пользователя или пароль.'], $form->getErrors('password'));
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
