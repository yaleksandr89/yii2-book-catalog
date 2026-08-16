<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\models\LoginForm;
use app\models\User;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
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

    #[TestDox('Несуществующее имя пользователя отклоняется без входа и раскрытия причины')]
    public function testUnknownUsernameIsRejectedWithGenericPasswordError(): void
    {
        $form = new LoginForm([
            'username' => 'missing-reader',
            'password' => 'any-password',
        ]);

        self::assertFalse($form->login());
        self::assertTrue(Yii::$app->user->isGuest);
        self::assertSame(['Неверное имя пользователя или пароль.'], $form->getErrors('password'));
    }

    #[TestDox('Сохраненный пользователь реализует контракты IdentityInterface')]
    public function testPersistedUserImplementsIdentityContracts(): void
    {
        $user = $this->createUser('identity-reader', 'correct-password');

        self::assertSame($user->id, User::findIdentity($user->id)?->id);
        self::assertNull(User::findIdentity(999999));
        self::assertSame($user->id, $user->getId());
        self::assertSame($user->auth_key, $user->getAuthKey());
        self::assertTrue($user->validateAuthKey($user->auth_key));
        self::assertFalse($user->validateAuthKey('wrong-auth-key'));
    }

    #[TestDox('Web-приложение не поддерживает access-token identity')]
    public function testAccessTokenIdentityIsNotSupported(): void
    {
        self::assertNull(User::findIdentityByAccessToken('unused-access-token'));
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
