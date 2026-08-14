<?php

declare(strict_types=1);

namespace app\services;

use app\models\Book;
use app\models\BookForm;
use Random\RandomException;
use RuntimeException;
use Throwable;
use Yii;
use yii\base\Exception as YiiBaseException;
use yii\db\Exception as YiiDbException;
use yii\helpers\FileHelper;

final class BookService
{
    private const string RELATIVE_DIRECTORY = 'uploads/books';

    private readonly string $storageRoot;

    public function __construct(string $storageRoot)
    {
        $this->storageRoot = Yii::getAlias($storageRoot);
    }

    /**
     * @throws Throwable
     */
    public function create(BookForm $form): Book
    {
        if ($form->image === null) {
            throw new RuntimeException('Для создания книги требуется изображение.');
        }

        $newImagePath = $this->saveImage($form);
        $transaction = null;

        try {
            $transaction = Yii::$app->db->beginTransaction();
            $book = new Book();
            $this->fillBook($book, $form);
            $book->image_path = $newImagePath;
            if (!$book->save(false)) {
                throw new RuntimeException('Не удалось сохранить книгу.');
            }

            $this->syncAuthors($book, $form->getNormalizedAuthorIds());
            $transaction->commit();

            return $book;
        } catch (Throwable $exception) {
            if ($transaction?->isActive) {
                $transaction->rollBack();
            }
            $this->removeNewImage($newImagePath);

            throw $exception;
        }
    }

    /**
     * @throws Throwable
     */
    public function update(Book $book, BookForm $form): Book
    {
        $oldAttributes = $book->getOldAttributes();
        $oldImagePath = $book->image_path;
        $newImagePath = $form->image === null ? null : $this->saveImage($form);
        $transaction = null;

        try {
            $transaction = Yii::$app->db->beginTransaction();
            $this->fillBook($book, $form);
            if ($newImagePath !== null) {
                $book->image_path = $newImagePath;
            }
            if (!$book->save(false)) {
                throw new RuntimeException('Не удалось обновить книгу.');
            }

            $this->syncAuthors($book, $form->getNormalizedAuthorIds());
            $transaction->commit();
        } catch (Throwable $exception) {
            if ($transaction?->isActive) {
                $transaction->rollBack();
            }
            if ($newImagePath !== null) {
                $this->removeNewImage($newImagePath);
            }
            $book->setAttributes($oldAttributes, false);
            $book->setOldAttributes($oldAttributes);

            throw $exception;
        }

        unset($book->authors);
        if ($newImagePath !== null) {
            $this->removeStoredImage($oldImagePath);
        }

        return $book;
    }

    /**
     * @throws Throwable
     */
    public function delete(Book $book): void
    {
        $imagePath = $book->image_path;
        $transaction = Yii::$app->db->beginTransaction();

        try {
            if ($book->delete() === false) {
                throw new RuntimeException('Не удалось удалить книгу.');
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            throw $exception;
        }

        $this->removeStoredImage($imagePath);
    }

    private function fillBook(Book $book, BookForm $form): void
    {
        $book->title = $form->title;
        $book->release_year = (int) $form->releaseYear;
        $book->description = $form->description;
        $book->isbn = $form->isbn;
    }

    /**
     * @param array<int> $authorIds
     * @throws YiiDbException
     */
    private function syncAuthors(Book $book, array $authorIds): void
    {
        Yii::$app->db->createCommand()
            ->delete('{{%book_author}}', ['book_id' => $book->id])
            ->execute();

        $rows = array_map(
            static fn (int $authorId): array => [$book->id, $authorId],
            $authorIds,
        );
        Yii::$app->db->createCommand()
            ->batchInsert('{{%book_author}}', ['book_id', 'author_id'], $rows)
            ->execute();
    }

    /**
     * @throws YiiBaseException
     * @throws RandomException
     */
    private function saveImage(BookForm $form): string
    {
        if (!FileHelper::createDirectory($this->storageRoot)) {
            throw new RuntimeException('Не удалось создать каталог изображений книг.');
        }

        $extension = strtolower($form->image->extension);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $relativePath = self::RELATIVE_DIRECTORY . '/' . $filename;
        if (!$form->image->saveAs($this->storageRoot . DIRECTORY_SEPARATOR . $filename, false)) {
            throw new RuntimeException('Не удалось сохранить изображение книги.');
        }

        return $relativePath;
    }

    private function removeNewImage(string $relativePath): void
    {
        $absolutePath = $this->resolveStoredPath($relativePath);
        if ($absolutePath !== null && is_file($absolutePath) && !unlink($absolutePath)) {
            Yii::warning('Не удалось удалить новое изображение после ошибки: ' . $relativePath, __METHOD__);
        }
    }

    private function removeStoredImage(string $relativePath): void
    {
        $absolutePath = $this->resolveStoredPath($relativePath);
        if ($absolutePath === null) {
            Yii::warning('Пропущено удаление изображения с небезопасным путём: ' . $relativePath, __METHOD__);
            return;
        }
        if (is_file($absolutePath) && !unlink($absolutePath)) {
            Yii::warning('Не удалось удалить изображение книги: ' . $relativePath, __METHOD__);
        }
    }

    private function resolveStoredPath(string $relativePath): ?string
    {
        $pattern = '#^' . preg_quote(self::RELATIVE_DIRECTORY, '#')
            . '/[a-f0-9]{32}\.(?:jpg|jpeg|png|webp)$#';
        if (preg_match($pattern, $relativePath) !== 1) {
            return null;
        }

        return $this->storageRoot . DIRECTORY_SEPARATOR . basename($relativePath);
    }
}
