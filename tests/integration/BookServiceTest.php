<?php

declare(strict_types=1);

namespace tests\integration;

use app\models\Author;
use app\models\Book;
use app\models\BookForm;
use app\services\BookService;
use PHPUnit\Framework\Attributes\TestDox;
use tests\TestCase;
use Yii;
use yii\db\Exception as YiiDbException;
use yii\web\UploadedFile;

final class BookServiceTest extends TestCase
{
    #[TestDox('Создание сохраняет книгу, авторов и изображение со сгенерированным именем')]
    public function testCreatePersistsBookAuthorsAndGeneratedImage(): void
    {
        [$firstAuthor, $secondAuthor] = $this->createAuthors();
        $form = $this->validForm([$firstAuthor->id, $secondAuthor->id], 'Созданная книга');

        $book = $this->service()->create($form);

        self::assertNotNull(Book::findOne($book->id));
        self::assertMatchesRegularExpression('#^uploads/books/[a-f0-9]{32}\.png$#', $book->image_path);
        self::assertFileExists($this->absolutePath($book->image_path));
        self::assertSame(
            [$firstAuthor->id, $secondAuthor->id],
            array_column($book->getAuthors()->orderBy(['id' => SORT_ASC])->all(), 'id'),
        );
    }

    #[TestDox('Обновление без изображения сохраняет прежний путь и файл')]
    public function testUpdateWithoutImagePreservesOldPathAndFile(): void
    {
        [$firstAuthor, $secondAuthor] = $this->createAuthors();
        $book = $this->service()->create($this->validForm([$firstAuthor->id], 'Старое название'));
        $oldImagePath = $book->image_path;
        self::assertSame([$firstAuthor->id], array_column($book->authors, 'id'));
        $form = $this->validForm([$secondAuthor->id], 'Новое название', false);

        $this->service()->update($book, $form);

        self::assertSame('Новое название', $book->title);
        self::assertSame($oldImagePath, $book->image_path);
        self::assertFileExists($this->absolutePath($oldImagePath));
        self::assertSame([$secondAuthor->id], array_column($book->authors, 'id'));
    }

    #[TestDox('Замена изображения сохраняет новый файл и удаляет старый после транзакции')]
    public function testReplaceImageChangesPathAndRemovesOldFile(): void
    {
        [$author] = $this->createAuthors();
        $book = $this->service()->create($this->validForm([$author->id], 'Книга'));
        $oldImagePath = $book->image_path;
        $form = $this->validForm([$author->id], 'Книга с новой обложкой');

        $this->service()->update($book, $form);

        self::assertNotSame($oldImagePath, $book->image_path);
        self::assertFileExists($this->absolutePath($book->image_path));
        self::assertFileDoesNotExist($this->absolutePath($oldImagePath));
    }

    #[TestDox('Удаление книги удаляет связи и изображение')]
    public function testDeleteRemovesBookRelationsAndImage(): void
    {
        [$author] = $this->createAuthors();
        $book = $this->service()->create($this->validForm([$author->id], 'Удаляемая книга'));
        $bookId = $book->id;
        $imagePath = $book->image_path;

        $this->service()->delete($book);

        self::assertNull(Book::findOne($bookId));
        self::assertSame(
            0,
            Yii::$app->db->createCommand('SELECT COUNT(*) FROM {{%book_author}} WHERE book_id = :bookId')
                ->bindValue(':bookId', $bookId)
                ->queryScalar(),
        );
        self::assertFileDoesNotExist($this->absolutePath($imagePath));
    }

    #[TestDox('Ошибка обновления откатывает БД, удаляет новый файл и сохраняет старую обложку')]
    public function testUpdateFailurePreservesOldImageAndRemovesNewImage(): void
    {
        [$firstAuthor, $secondAuthor] = $this->createAuthors();
        $book = $this->service()->create($this->validForm([$firstAuthor->id], 'Исходная книга'));
        $oldImagePath = $book->image_path;
        $form = $this->validForm([$secondAuthor->id], 'Не сохранится');
        self::assertNotFalse($secondAuthor->delete());

        try {
            $this->service()->update($book, $form);
        } catch (YiiDbException) {
            $persistedBook = Book::findOne($book->id);
            self::assertNotNull($persistedBook);
            self::assertSame('Исходная книга', $persistedBook->title);
            self::assertSame($oldImagePath, $persistedBook->image_path);
            self::assertFileExists($this->absolutePath($oldImagePath));
            self::assertSame([$this->absolutePath($oldImagePath)], $this->generatedImages());
            self::assertFileExists($form->image->tempName);

            return;
        }

        self::fail('Ожидалась ошибка внешнего ключа при сохранении связи с удалённым автором.');
    }

    /**
     * @return string[]
     */
    private function generatedImages(): array
    {
        return glob(Yii::getAlias('@runtime/test-book-uploads/[0-9a-f]*.png')) ?: [];
    }

    /**
     * @return array{Author, Author}
     */
    private function createAuthors(): array
    {
        $firstAuthor = new Author(['full_name' => 'Первый Автор']);
        $secondAuthor = new Author(['full_name' => 'Второй Автор']);
        self::assertTrue($firstAuthor->save());
        self::assertTrue($secondAuthor->save());

        return [$firstAuthor, $secondAuthor];
    }

    /**
     * @param array<int> $authorIds
     */
    private function validForm(array $authorIds, string $title, bool $withImage = true): BookForm
    {
        $form = new BookForm([
            'scenario' => $withImage ? BookForm::SCENARIO_CREATE : BookForm::SCENARIO_UPDATE,
            'title' => $title,
            'releaseYear' => '2024',
            'description' => 'Описание книги.',
            'isbn' => '978-5-00-123456-7',
            'authorIds' => array_map('strval', $authorIds),
            'image' => $withImage ? $this->uploadedPng() : null,
        ]);
        self::assertTrue($form->validate(), json_encode($form->errors, JSON_UNESCAPED_UNICODE));

        return $form;
    }

    private function uploadedPng(): UploadedFile
    {
        $path = Yii::getAlias('@runtime/test-book-uploads/source-' . uniqid('', true) . '.png');
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        self::assertIsString($contents);
        self::assertNotFalse(file_put_contents($path, $contents));

        return new UploadedFile([
            'name' => 'ignored-original-name.png',
            'tempName' => $path,
            'type' => 'image/png',
            'size' => filesize($path),
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    private function service(): BookService
    {
        return new BookService((string) Yii::$app->params['bookImageStorageRoot']);
    }

    private function absolutePath(string $relativePath): string
    {
        return Yii::getAlias('@runtime/test-book-uploads/' . basename($relativePath));
    }
}
