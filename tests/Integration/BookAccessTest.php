<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\models\Author;
use app\models\Book;
use app\models\User;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yii;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;

final class BookAccessTest extends TestCase
{
    #[TestDox('Гость может просматривать список и карточку книги')]
    public function testGuestCanBrowseBooks(): void
    {
        $book = $this->createBook();

        self::assertIsString($this->app->runAction('book/index'));
        self::assertIsString($this->app->runAction('book/view', ['id' => $book->id]));
    }

    #[TestDox('Первая страница списка книг содержит не более десяти книг')]
    public function testFirstBookPageContainsOnlyTenBooks(): void
    {
        for ($number = 1; $number <= 11; $number++) {
            $this->createBook(sprintf('Книга %02d', $number));
        }

        $html = $this->app->runAction('book/index');

        self::assertSame(11, substr_count($html, '<tr>'));
        self::assertStringNotContainsString('Книга 11', $html);
    }

    #[TestDox('Гость не может открыть создание книги')]
    public function testGuestCannotCreateBook(): void
    {
        $this->app->runAction('book/create');

        self::assertSame(302, $this->app->response->statusCode);
    }

    #[TestDox('Гость не может открыть редактирование книги')]
    public function testGuestCannotUpdateBook(): void
    {
        $book = $this->createBook();

        $this->app->runAction('book/update', ['id' => $book->id]);

        self::assertSame(302, $this->app->response->statusCode);
    }

    #[TestDox('Гость не может удалить книгу')]
    public function testGuestCannotDeleteBook(): void
    {
        $book = $this->createBook();

        $this->app->runAction('book/delete', ['id' => $book->id]);

        self::assertSame(302, $this->app->response->statusCode);
    }

    #[TestDox('Аутентифицированный пользователь может открыть создание и редактирование книги')]
    public function testAuthenticatedUserCanOpenCreateAndUpdate(): void
    {
        $book = $this->createBook();
        $this->login();

        self::assertIsString($this->app->runAction('book/create'));
        self::assertIsString($this->app->runAction('book/update', ['id' => $book->id]));
    }

    #[TestDox('GET-запрос на удаление книги отклоняется')]
    public function testDeleteGetIsRejectedForAuthenticatedUser(): void
    {
        $book = $this->createBook();
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        $this->app->runAction('book/delete', ['id' => $book->id]);
    }

    #[TestDox('Аутентифицированный пользователь удаляет книгу POST-запросом')]
    public function testAuthenticatedPostDeleteWorks(): void
    {
        $book = $this->createBook();
        $this->login();
        $this->setRequestMethod('POST');

        $this->app->runAction('book/delete', ['id' => $book->id]);

        self::assertNull(Book::findOne($book->id));
    }

    #[TestDox('Запрос отсутствующей книги возвращает 404')]
    public function testMissingBookReturnsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->app->runAction('book/view', ['id' => 999999]);
    }

    #[TestDox('Аутентифицированный пользователь создаёт книгу с загруженной обложкой')]
    public function testAuthenticatedUserCanCreateBookFromPostWithUpload(): void
    {
        $author = $this->createAuthor('Автор новой книги');
        $this->login();
        $_POST = ['BookForm' => [
            'title' => 'Книга из HTTP-формы',
            'releaseYear' => '2024',
            'description' => 'Описание книги из формы.',
            'isbn' => '978-5-00-000010-9',
            'authorIds' => [(string) $author->id],
        ]];
        $this->setUploadedPng();
        $this->setRequestMethod('POST');

        $this->app->runAction('book/create');

        $book = Book::find()->where(['title' => 'Книга из HTTP-формы'])->one();
        self::assertNotNull($book);
        self::assertSame(302, $this->app->response->statusCode);
        self::assertSame([$author->id], array_column($book->authors, 'id'));
        self::assertFileExists(Yii::getAlias('@runtime/test-book-uploads/' . basename($book->image_path)));
    }

    #[TestDox('Аутентифицированный пользователь обновляет книгу без замены обложки')]
    public function testAuthenticatedUserCanUpdateBookFromPostWithoutUpload(): void
    {
        $book = $this->createBook();
        $replacementAuthor = $this->createAuthor('Новый автор книги');
        $oldImagePath = $book->image_path;
        $oldImageFile = Yii::getAlias('@runtime/test-book-uploads/' . basename($oldImagePath));
        self::assertNotFalse(file_put_contents($oldImageFile, 'old cover'));
        $this->login();
        $_POST = ['BookForm' => [
            'title' => 'Обновлённая книга из HTTP-формы',
            'releaseYear' => '2025',
            'description' => 'Новое описание книги.',
            'isbn' => '978-5-00-000011-6',
            'authorIds' => [(string) $replacementAuthor->id],
        ]];
        $this->setRequestMethod('POST');

        $this->app->runAction('book/update', ['id' => $book->id]);

        $persistedBook = Book::findOne($book->id);
        self::assertNotNull($persistedBook);
        self::assertSame('Обновлённая книга из HTTP-формы', $persistedBook->title);
        self::assertSame($oldImagePath, $persistedBook->image_path);
        self::assertSame([$replacementAuthor->id], array_column($persistedBook->authors, 'id'));
        self::assertFileExists($oldImageFile);
        self::assertSame(302, $this->app->response->statusCode);
    }

    private function createBook(string $title = 'Тестовая книга'): Book
    {
        $author = new Author(['full_name' => 'Тестовый Автор']);
        self::assertTrue($author->save());
        $book = new Book([
            'title' => $title,
            'release_year' => 2025,
            'description' => 'Описание тестовой книги.',
            'isbn' => '978-5-00-000002-4',
            'image_path' => 'uploads/books/' . str_repeat('b', 32) . '.png',
        ]);
        self::assertTrue($book->save());
        Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => $author->id,
        ])->execute();

        return $book;
    }

    private function createAuthor(string $fullName): Author
    {
        $author = new Author(['full_name' => $fullName]);
        self::assertTrue($author->save());

        return $author;
    }

    private function setUploadedPng(): void
    {
        $path = Yii::getAlias('@runtime/test-book-uploads/http-upload.png');
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        self::assertIsString($contents);
        self::assertNotFalse(file_put_contents($path, $contents));
        $_FILES = ['BookForm' => [
            'name' => ['image' => 'http-upload.png'],
            'type' => ['image' => 'image/png'],
            'tmp_name' => ['image' => $path],
            'error' => ['image' => UPLOAD_ERR_OK],
            'size' => ['image' => filesize($path)],
        ]];
    }

    private function login(): void
    {
        $user = new User([
            'username' => 'book-editor',
            'password_hash' => Yii::$app->security->generatePasswordHash('password'),
            'auth_key' => Yii::$app->security->generateRandomString(),
        ]);
        self::assertTrue($user->save());
        self::assertTrue(Yii::$app->user->login($user));
    }
}
