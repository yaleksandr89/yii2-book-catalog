<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\integrations\SmsSenderInterface;
use app\models\Author;
use app\models\Book;
use app\models\BookForm;
use app\models\Subscription;
use app\services\BookService;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;
use Tests\TestCase;
use Yii;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\Exception as YiiDbException;
use yii\helpers\FileHelper;
use yii\log\Logger;
use yii\web\UploadedFile;

final class BookServiceTest extends TestCase
{
    #[TestDox('Создание книги требует изображение на границе сервиса')]
    public function testCreateRequiresImage(): void
    {
        $form = BookForm::forCreate();

        try {
            $this->service()->create($form);
        } catch (RuntimeException $exception) {
            self::assertSame('Для создания книги требуется изображение.', $exception->getMessage());
            self::assertSame(0, (int) Book::find()->count());
            self::assertSame([], $this->generatedImages());

            return;
        }

        self::fail('Ожидалась ошибка создания книги без изображения.');
    }

    #[TestDox('Ошибка записи файла обложки не создаёт книгу')]
    public function testCreateStopsWhenImageStorageRejectsWrite(): void
    {
        [$author] = $this->createAuthors();
        $image = $this->getMockBuilder(UploadedFile::class)
            ->onlyMethods(['saveAs'])
            ->getMock();
        $image->name = 'failed-cover.png';
        $image->expects(self::once())->method('saveAs')->willReturn(false);
        $form = new BookForm([
            'scenario' => BookForm::SCENARIO_CREATE,
            'title' => 'Книга без записанной обложки',
            'releaseYear' => '2024',
            'description' => 'Описание книги.',
            'isbn' => '978-5-00-765432-1',
            'authorIds' => [(string) $author->id],
            'image' => $image,
        ]);

        try {
            $this->service()->create($form);
        } catch (RuntimeException $exception) {
            self::assertSame('Не удалось сохранить изображение книги.', $exception->getMessage());
            self::assertSame(0, (int) Book::find()->count());
            self::assertSame([], $this->generatedImages());

            return;
        }

        self::fail('Ожидалась ошибка записи файла обложки.');
    }

    #[TestDox('Создание показывает ошибку файловой системы, когда каталог обложек нельзя создать')]
    public function testCreateFailsWhenImageStorageDirectoryCannotBeCreated(): void
    {
        $fixtureParent = $this->createFilesystemFixture('directory');
        $storageRoot = $fixtureParent . '/storage';
        $parentMode = $this->directoryMode($fixtureParent);
        [$author] = $this->createAuthors();
        $form = $this->validForm([$author->id], 'Книга без каталога обложек');

        self::assertTrue(chmod($fixtureParent, 0555));

        try {
            $exception = null;

            try {
                $this->serviceAt($storageRoot)->create($form);
            } catch (RuntimeException $caught) {
                $exception = $caught;
            }

            self::assertInstanceOf(RuntimeException::class, $exception);
            self::assertSame('Не удалось создать каталог изображений книг.', $exception->getMessage());
            self::assertFalse(is_dir($storageRoot));
            self::assertSame(0, (int) Book::find()->count());
        } finally {
            chmod($fixtureParent, $parentMode);
            FileHelper::removeDirectory($fixtureParent);
        }
    }

    #[TestDox('Создание сохраняет новую обложку и пишет предупреждение, если её нельзя удалить после ошибки записи')]
    public function testCreateKeepsNewImageAndLogsWarningWhenRollbackCleanupCannotUnlinkIt(): void
    {
        $fixtureParent = $this->createFilesystemFixture('new-image-cleanup');
        $storageRoot = $fixtureParent . '/storage';
        FileHelper::createDirectory($storageRoot);
        $storageMode = $this->directoryMode($storageRoot);
        [$author] = $this->createAuthors();
        $logger = new Logger(['flushInterval' => 0, 'traceLevel' => 0]);
        $originalLogger = Yii::getLogger();
        $handler = static function (ModelEvent $event) use ($storageRoot): void {
            chmod($storageRoot, 0555);
            throw new RuntimeException('Injected persistence failure.');
        };

        Yii::setLogger($logger);
        Event::on(Book::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);

        try {
            try {
                $this->serviceAt($storageRoot)->create($this->validForm([$author->id], 'Книга с неочищаемой обложкой'));
                self::fail('Expected the injected persistence failure.');
            } catch (RuntimeException $exception) {
                self::assertSame('Injected persistence failure.', $exception->getMessage());
            }

            $images = glob($storageRoot . '/*') ?: [];

            self::assertCount(1, $images);
            self::assertSame(0, (int) Book::find()->count());
            $relativePath = 'uploads/books/' . basename($images[0]);
            $this->assertWarningLogged(
                $logger,
                'Не удалось удалить новое изображение после ошибки: ' . $relativePath,
                BookService::class . '::removeNewImage',
            );
        } finally {
            Event::off(Book::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);
            Yii::setLogger($originalLogger);
            chmod($storageRoot, $storageMode);
            FileHelper::removeDirectory($fixtureParent);
        }
    }

    #[TestDox('Удаление книги фиксирует запись и предупреждает, если файл обложки нельзя удалить')]
    public function testDeleteKeepsStoredImageAndLogsWarningWhenUnlinkFails(): void
    {
        $fixtureParent = $this->createFilesystemFixture('stored-image-cleanup');
        $storageRoot = $fixtureParent . '/storage';
        FileHelper::createDirectory($storageRoot);
        $storageMode = $this->directoryMode($storageRoot);
        [$author] = $this->createAuthors();
        $service = $this->serviceAt($storageRoot);
        $book = $service->create($this->validForm([$author->id], 'Книга с неочищаемой обложкой'));
        $storedImage = $storageRoot . '/' . basename($book->image_path);
        $logger = new Logger(['flushInterval' => 0, 'traceLevel' => 0]);
        $originalLogger = Yii::getLogger();

        Yii::setLogger($logger);
        self::assertTrue(chmod($storageRoot, 0555));

        try {
            $service->delete($book);

            self::assertNull(Book::findOne($book->id));
            self::assertFileExists($storedImage);
            $this->assertWarningLogged(
                $logger,
                'Не удалось удалить изображение книги: ' . $book->image_path,
                BookService::class . '::removeStoredImage',
            );
        } finally {
            Yii::setLogger($originalLogger);
            chmod($storageRoot, $storageMode);
            FileHelper::removeDirectory($fixtureParent);
        }
    }

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

    #[TestDox('SMS-уведомление отправляется только после фиксации новой книги')]
    public function testCreateNotifiesSubscriberAfterCommit(): void
    {
        [$author] = $this->createAuthors();
        $phone = '79990000001';
        $title = 'Книга после коммита';
        $this->createSubscription($author, $phone);
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (
                string $actualPhone,
                string $message,
            ) use (
                $author,
                $phone,
                $title,
            ): void {
                self::assertSame($phone, $actualPhone);
                self::assertSame('Новая книга у автора: ' . $author->full_name . '.', $message);
                self::assertNotNull(Book::find()->where(['title' => $title])->one());
                self::assertNull(Yii::$app->db->getTransaction());
            });

        $book = $this->serviceWith($sender)->create($this->validForm([$author->id], $title));

        self::assertNotNull(Book::findOne($book->id));
    }

    #[TestDox('Один телефон с подписками на двух авторов получает одно SMS')]
    public function testCreateDeduplicatesPhoneAcrossAuthors(): void
    {
        [$firstAuthor, $secondAuthor] = $this->createAuthors();
        $phone = '79990000002';
        $this->createSubscription($firstAuthor, $phone);
        $this->createSubscription($secondAuthor, $phone);
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects(self::once())
            ->method('send')
            ->with($phone, 'Новая книга у авторов из ваших подписок.');

        $this->serviceWith($sender)->create(
            $this->validForm([$firstAuthor->id, $secondAuthor->id], 'Книга двух авторов'),
        );
    }

    #[TestDox('Подписчик одного автора книги получает имя только этого автора')]
    public function testCreateNamesOnlyMatchingSubscribedAuthor(): void
    {
        [$firstAuthor, $secondAuthor] = $this->createAuthors();
        $phone = '79990000004';
        $this->createSubscription($secondAuthor, $phone);
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects(self::once())
            ->method('send')
            ->with($phone, 'Новая книга у автора: ' . $secondAuthor->full_name . '.');

        $this->serviceWith($sender)->create(
            $this->validForm([$firstAuthor->id, $secondAuthor->id], 'Книга двух авторов'),
        );
    }

    #[TestDox('Все уникальные подписчики получают попытку отправки в порядке телефона')]
    public function testCreateAttemptsAllUniqueSubscribersInPhoneOrder(): void
    {
        [$author] = $this->createAuthors();
        $phones = ['79990000003', '79990000001', '79990000002'];
        foreach ($phones as $phone) {
            $this->createSubscription($author, $phone);
        }
        $actualPhones = [];
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects(self::exactly(3))
            ->method('send')
            ->willReturnCallback(static function (string $phone) use (&$actualPhones): void {
                $actualPhones[] = $phone;
            });

        $this->serviceWith($sender)->create($this->validForm([$author->id], 'Книга для трёх подписчиков'));

        self::assertSame(['79990000001', '79990000002', '79990000003'], $actualPhones);
    }

    #[TestDox('Сбой SMSPilot не повреждает книгу и не останавливает следующего подписчика')]
    public function testProviderFailurePreservesBookAndContinuesRecipients(): void
    {
        [$firstAuthor, $secondAuthor] = $this->createAuthors();
        $firstPhone = '79990000001';
        $secondPhone = '79990000002';
        $secret = 'do-not-log-api-key';
        $this->createSubscription($firstAuthor, $firstPhone);
        $this->createSubscription($secondAuthor, $firstPhone);
        $this->createSubscription($secondAuthor, $secondPhone);
        $attemptedPhones = [];
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(
                static function (string $phone) use (&$attemptedPhones, $firstPhone, $secret): void {
                    $attemptedPhones[] = $phone;
                    if ($phone === $firstPhone) {
                        throw new RuntimeException('Injected provider failure: ' . $secret);
                    }
                },
            );
        $logger = new Logger(['flushInterval' => 0, 'traceLevel' => 0]);
        $originalLogger = Yii::getLogger();
        Yii::setLogger($logger);

        try {
            $book = $this->serviceWith($sender)->create(
                $this->validForm([$firstAuthor->id, $secondAuthor->id], 'Книга при сбое провайдера'),
            );
        } finally {
            Yii::setLogger($originalLogger);
        }

        self::assertNotNull(Book::findOne($book->id));
        self::assertFileExists($this->absolutePath($book->image_path));
        self::assertSame(
            [$firstAuthor->id, $secondAuthor->id],
            array_column($book->getAuthors()->orderBy(['id' => SORT_ASC])->all(), 'id'),
        );
        self::assertSame([$firstPhone, $secondPhone], $attemptedPhones);
        $this->assertWarningLogged(
            $logger,
            'Не удалось отправить SMS-уведомление подписчику; книга уже сохранена.',
            BookService::class . '::notifySubscribers',
        );
        self::assertStringNotContainsString($secret, var_export($logger->messages, true));
    }

    #[TestDox('Обновление книги не отправляет SMS подписчикам')]
    public function testUpdateDoesNotNotifySubscribers(): void
    {
        [$author] = $this->createAuthors();
        $sender = $this->createMock(SmsSenderInterface::class);
        $sender->expects(self::never())->method('send');
        $service = $this->serviceWith($sender);
        $book = $service->create($this->validForm([$author->id], 'Книга до обновления'));
        $this->createSubscription($author, '79990000004');

        $service->update($book, $this->validForm([$author->id], 'Книга после обновления', false));

        self::assertSame('Книга после обновления', $book->title);
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

    #[TestDox('Удаление книги не затрагивает файл с небезопасным сохранённым путём')]
    public function testDeleteDoesNotRemoveFileForUnsafeStoredPath(): void
    {
        [$author] = $this->createAuthors();
        $protectedPath = Yii::getAlias('@runtime/test-assets/protected.png');
        self::assertNotFalse(file_put_contents($protectedPath, 'protected'));
        $book = new Book([
            'title' => 'Книга с небезопасным путём',
            'release_year' => 2024,
            'description' => 'Описание книги.',
            'isbn' => '978-5-00-123456-7',
            'image_path' => '../test-assets/protected.png',
        ]);
        self::assertTrue($book->save());
        Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => $author->id,
        ])->execute();

        try {
            $this->service()->delete($book);

            self::assertFileExists($protectedPath);
        } finally {
            if (is_file($protectedPath)) {
                unlink($protectedPath);
            }
        }
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

    #[TestDox('Ошибка создания откатывает БД и удаляет новую обложку')]
    public function testCreateFailureRollsBackBookAndRemovesNewImage(): void
    {
        [$author] = $this->createAuthors();
        $form = $this->validForm([$author->id], 'Не сохранится при создании');
        $sourcePath = $form->image?->tempName;
        self::assertIsString($sourcePath);
        self::assertNotFalse($author->delete());

        try {
            $this->service()->create($form);
        } catch (YiiDbException) {
            self::assertNull(Book::find()->where(['title' => 'Не сохранится при создании'])->one());
            self::assertSame([], $this->generatedImages());
            self::assertFileExists($sourcePath);
            $relationCount = Yii::$app->db
                ->createCommand('SELECT COUNT(*) FROM {{%book_author}}')
                ->queryScalar();
            self::assertSame(0, (int) $relationCount);

            return;
        }

        self::fail('Ожидалась ошибка внешнего ключа при сохранении связи с удалённым автором.');
    }

    #[TestDox('Отказ ActiveRecord сохранить новую книгу откатывает запись и удаляет обложку')]
    public function testCreateSaveRejectionRollsBackAndRemovesImage(): void
    {
        [$author] = $this->createAuthors();
        $form = $this->validForm([$author->id], 'Отклонённая новая книга');
        $handler = static function (ModelEvent $event): void {
            $event->isValid = false;
        };
        Event::on(Book::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);

        try {
            $this->service()->create($form);
            self::fail('Ожидался отказ ActiveRecord сохранить новую книгу.');
        } catch (RuntimeException $exception) {
            self::assertSame('Не удалось сохранить книгу.', $exception->getMessage());
            self::assertNull(Book::find()->where(['title' => 'Отклонённая новая книга'])->one());
            self::assertSame([], $this->generatedImages());
        } finally {
            Event::off(Book::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        }
    }

    #[TestDox('Отказ ActiveRecord обновить книгу сохраняет прежние данные и обложку')]
    public function testUpdateSaveRejectionPreservesExistingBookAndImage(): void
    {
        [$author] = $this->createAuthors();
        $book = $this->service()->create($this->validForm([$author->id], 'Исходная книга'));
        $oldImagePath = $book->image_path;
        $form = $this->validForm([$author->id], 'Отклонённое обновление');
        $handler = static function (ModelEvent $event): void {
            $event->isValid = false;
        };
        Event::on(Book::class, ActiveRecord::EVENT_BEFORE_UPDATE, $handler);

        try {
            $this->service()->update($book, $form);
            self::fail('Ожидался отказ ActiveRecord обновить книгу.');
        } catch (RuntimeException $exception) {
            $persistedBook = Book::findOne($book->id);
            self::assertSame('Не удалось обновить книгу.', $exception->getMessage());
            self::assertNotNull($persistedBook);
            self::assertSame('Исходная книга', $persistedBook->title);
            self::assertSame($oldImagePath, $persistedBook->image_path);
            self::assertSame([$this->absolutePath($oldImagePath)], $this->generatedImages());
        } finally {
            Event::off(Book::class, ActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        }
    }

    #[TestDox('Отказ ActiveRecord удалить книгу откатывает удаление и сохраняет обложку')]
    public function testDeleteRejectionPreservesBookAndImage(): void
    {
        [$author] = $this->createAuthors();
        $book = $this->service()->create($this->validForm([$author->id], 'Неудалённая книга'));
        $imagePath = $book->image_path;
        $handler = static function (ModelEvent $event): void {
            $event->isValid = false;
        };
        Event::on(Book::class, ActiveRecord::EVENT_BEFORE_DELETE, $handler);

        try {
            $this->service()->delete($book);
            self::fail('Ожидался отказ ActiveRecord удалить книгу.');
        } catch (RuntimeException $exception) {
            self::assertSame('Не удалось удалить книгу.', $exception->getMessage());
            self::assertNotNull(Book::findOne($book->id));
            self::assertFileExists($this->absolutePath($imagePath));
        } finally {
            Event::off(Book::class, ActiveRecord::EVENT_BEFORE_DELETE, $handler);
        }
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
        return $this->serviceWith($this->createStub(SmsSenderInterface::class));
    }

    private function serviceWith(SmsSenderInterface $smsSender): BookService
    {
        return new BookService(
            (string) Yii::$app->params['bookImageStorageRoot'],
            $smsSender,
        );
    }

    private function serviceAt(string $storageRoot): BookService
    {
        return new BookService($storageRoot, $this->createStub(SmsSenderInterface::class));
    }

    private function createSubscription(Author $author, string $phone): void
    {
        $subscription = new Subscription([
            'author_id' => $author->id,
            'phone' => $phone,
        ]);
        self::assertTrue($subscription->save());
    }

    private function createFilesystemFixture(string $name): string
    {
        $path = Yii::getAlias('@runtime/book-service-filesystem-' . $name . '-' . uniqid('', true));
        FileHelper::createDirectory($path);

        return $path;
    }

    private function directoryMode(string $path): int
    {
        $mode = fileperms($path);

        self::assertIsInt($mode);

        return $mode & 0777;
    }

    private function assertWarningLogged(Logger $logger, string $message, string $category): void
    {
        foreach ($logger->messages as $entry) {
            if (
                is_array($entry)
                && $entry[0] === $message
                && $entry[1] === Logger::LEVEL_WARNING
                && $entry[2] === $category
            ) {
                return;
            }
        }

        self::fail('Expected warning was not logged: ' . $message);
    }

    private function absolutePath(string $relativePath): string
    {
        return Yii::getAlias('@runtime/test-book-uploads/' . basename($relativePath));
    }
}
