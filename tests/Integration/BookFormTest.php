<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\models\Author;
use app\models\BookForm;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yii;
use yii\web\UploadedFile;

final class BookFormTest extends TestCase
{
    #[TestDox('Корректные данные и существующие авторы проходят валидацию')]
    public function testValidDataWithExistingAuthorsPasses(): void
    {
        $firstAuthor = $this->createAuthor('Аркадий Стругацкий');
        $secondAuthor = $this->createAuthor('Борис Стругацкий');
        $form = $this->validCreateForm([$firstAuthor->id, (string) $secondAuthor->id, $firstAuthor->id]);

        self::assertTrue($form->validate(), json_encode($form->errors, JSON_UNESCAPED_UNICODE));
        self::assertSame([$firstAuthor->id, $secondAuthor->id], $form->getNormalizedAuthorIds());
    }

    #[TestDox('Книга без авторов отклоняется')]
    public function testMissingAuthorsAreRejected(): void
    {
        $form = $this->validCreateForm([]);

        self::assertFalse($form->validate());
        self::assertNotEmpty($form->getErrors('authorIds'));
    }

    #[TestDox('Неизвестный автор отклоняется')]
    public function testUnknownAuthorIsRejected(): void
    {
        $form = $this->validCreateForm([999999]);

        self::assertFalse($form->validate());
        self::assertNotEmpty($form->getErrors('authorIds'));
    }

    #[TestDox('Создание без изображения отклоняется')]
    public function testCreateWithoutImageIsRejected(): void
    {
        $author = $this->createAuthor('Иван Бунин');
        $form = $this->validCreateForm([$author->id]);
        $form->image = null;

        self::assertFalse($form->validate());
        self::assertNotEmpty($form->getErrors('image'));
    }

    #[TestDox('Обновление без нового изображения разрешено')]
    public function testUpdateWithoutImageIsAllowed(): void
    {
        $author = $this->createAuthor('Михаил Булгаков');
        $form = new BookForm([
            'scenario' => BookForm::SCENARIO_UPDATE,
            'title' => 'Мастер и Маргарита',
            'releaseYear' => '1967',
            'description' => 'Роман.',
            'isbn' => '978-5-00-000000-1',
            'authorIds' => [(string) $author->id],
        ]);

        self::assertTrue($form->validate(), json_encode($form->errors, JSON_UNESCAPED_UNICODE));
    }

    #[TestDox('Слишком длинные поля и нечетырёхзначный год отклоняются')]
    public function testInvalidLengthsAndYearAreRejected(): void
    {
        $author = $this->createAuthor('Александр Пушкин');
        $form = $this->validCreateForm([$author->id]);
        $form->title = str_repeat('А', 256);
        $form->isbn = str_repeat('1', 33);
        $form->releaseYear = 999;

        self::assertFalse($form->validate());
        self::assertNotEmpty($form->getErrors('title'));
        self::assertNotEmpty($form->getErrors('isbn'));
        self::assertNotEmpty($form->getErrors('releaseYear'));
    }

    /**
     * @param array<array-key, int|string> $authorIds
     */
    private function validCreateForm(array $authorIds): BookForm
    {
        return new BookForm([
            'scenario' => BookForm::SCENARIO_CREATE,
            'title' => 'Пикник на обочине',
            'releaseYear' => '1972',
            'description' => 'Фантастический роман.',
            'isbn' => '978-5-00-000000-0',
            'authorIds' => $authorIds,
            'image' => $this->uploadedPng(),
        ]);
    }

    private function createAuthor(string $name): Author
    {
        $author = new Author(['full_name' => $name]);
        self::assertTrue($author->save());

        return $author;
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
            'name' => 'original-name.png',
            'tempName' => $path,
            'type' => 'image/png',
            'size' => filesize($path),
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    #[TestDox('HTTP-данные формы не перезаписывают объект загруженного изображения строкой')]
    public function testFormLoadDoesNotMassAssignImage(): void
    {
        $author = $this->createAuthor('Рэй Брэдбери');
        $form = BookForm::forCreate();

        self::assertTrue($form->load([
            'BookForm' => [
                'title' => '451 градус по Фаренгейту',
                'releaseYear' => '1953',
                'description' => 'Роман.',
                'isbn' => '978-5-00-000003-1',
                'authorIds' => [(string) $author->id],
                'image' => '',
            ],
        ]));

        self::assertNull($form->image);

        $form->image = $this->uploadedPng();

        self::assertTrue(
            $form->validate(),
            json_encode($form->errors, JSON_UNESCAPED_UNICODE),
        );
    }
}
