<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\models\Author;
use app\models\User;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yii;
use yii\web\MethodNotAllowedHttpException;

final class AuthorAccessTest extends TestCase
{
    #[TestDox('Гость может просматривать список и карточку автора')]
    public function testGuestCanBrowseAuthors(): void
    {
        $author = $this->createAuthor();

        self::assertIsString($this->app->runAction('author/index'));
        self::assertIsString($this->app->runAction('author/view', ['id' => $author->id]));
    }

    #[TestDox('Гость не может открыть создание автора')]
    public function testGuestCannotCreateAuthor(): void
    {
        $this->app->runAction('author/create');

        self::assertSame(302, $this->app->response->statusCode);
    }

    #[TestDox('Гость не может открыть редактирование автора')]
    public function testGuestCannotUpdateAuthor(): void
    {
        $author = $this->createAuthor();

        $this->app->runAction('author/update', ['id' => $author->id]);

        self::assertSame(302, $this->app->response->statusCode);
    }

    #[TestDox('Гость не может удалить автора')]
    public function testGuestCannotDeleteAuthor(): void
    {
        $author = $this->createAuthor();

        $this->app->runAction('author/delete', ['id' => $author->id]);

        self::assertSame(302, $this->app->response->statusCode);
    }

    #[TestDox('Аутентифицированный пользователь может открыть создание и редактирование автора')]
    public function testAuthenticatedUserCanOpenCreateAndUpdate(): void
    {
        $author = $this->createAuthor();
        $this->login();

        self::assertIsString($this->app->runAction('author/create'));
        self::assertIsString($this->app->runAction('author/update', ['id' => $author->id]));
    }

    #[TestDox('GET-запрос на удаление отклоняется')]
    public function testDeleteGetIsRejectedForAuthenticatedUser(): void
    {
        $author = $this->createAuthor();
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        $this->app->runAction('author/delete', ['id' => $author->id]);
    }

    #[TestDox('Аутентифицированный пользователь удаляет автора POST-запросом')]
    public function testAuthenticatedPostDeleteWorks(): void
    {
        $author = $this->createAuthor();
        $this->login();
        $this->setRequestMethod('POST');

        $this->app->runAction('author/delete', ['id' => $author->id]);

        self::assertNull(Author::findOne($author->id));
    }

    private function createAuthor(): Author
    {
        $author = new Author(['full_name' => 'Антон Чехов']);
        self::assertTrue($author->save());

        return $author;
    }

    private function login(): void
    {
        $user = new User([
            'username' => 'editor',
            'password_hash' => Yii::$app->security->generatePasswordHash('password'),
            'auth_key' => Yii::$app->security->generateRandomString(),
        ]);
        self::assertTrue($user->save());
        self::assertTrue(Yii::$app->user->login($user));
    }
}
