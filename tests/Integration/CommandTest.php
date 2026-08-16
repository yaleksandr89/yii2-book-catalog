<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\models\Author;
use app\models\Book;
use app\models\BookForm;
use app\models\User;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yii;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\console\Application as ConsoleApplication;
use yii\console\ExitCode;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;

final class CommandTest extends TestCase
{
    private const string OUTPUT_FILTER = 'tests.command-output-sink';

    #[TestDox('Команда создания пользователя сохраняет корректную учётную запись')]
    public function testUserCreatePersistsValidUser(): void
    {
        $exitCode = $this->runConsoleAction('user/create', ['console-reader', 'correct-password']);

        $user = User::findByUsername('console-reader');
        self::assertSame(ExitCode::OK, $exitCode);
        self::assertNotNull($user);
        self::assertTrue($user->validatePassword('correct-password'));
    }

    #[TestDox('Команда создания пользователя отклоняет повторяющееся имя')]
    public function testUserCreateRejectsDuplicateUsername(): void
    {
        self::assertSame(
            ExitCode::OK,
            $this->runConsoleAction('user/create', ['duplicate-reader', 'correct-password']),
        );

        $exitCode = $this->runConsoleAction('user/create', ['duplicate-reader', 'another-password']);

        self::assertSame(ExitCode::DATAERR, $exitCode);
        self::assertSame(1, (int) User::find()->where(['username' => 'duplicate-reader'])->count());
    }

    #[TestDox('Команда создания пользователя требует имя и пароль')]
    public function testUserCreateRejectsMissingUsernameOrPassword(): void
    {
        self::assertSame(
            ExitCode::DATAERR,
            $this->runConsoleAction('user/create', ['', 'correct-password']),
        );
        self::assertSame(
            ExitCode::DATAERR,
            $this->runConsoleAction('user/create', ['console-reader', '']),
        );
        self::assertSame(0, (int) User::find()->count());
    }

    #[TestDox('Команда создания пользователя отклоняет имя длиннее ограничения модели и схемы')]
    public function testUserCreateRejectsUsernameLongerThanSchemaLimit(): void
    {
        $usernameColumn = User::getTableSchema()->columns['username'] ?? null;
        self::assertNotNull($usernameColumn);
        $username = str_repeat('u', $usernameColumn->size + 1);

        $exitCode = $this->runConsoleAction('user/create', [$username, 'correct-password']);

        self::assertSame(ExitCode::DATAERR, $exitCode);
        self::assertSame(0, (int) User::find()->where(['username' => $username])->count());
    }

    #[TestDox('Команда демо-данных создаёт детерминированный каталог и изображения')]
    public function testDemoDataFillCreatesDeterministicCatalog(): void
    {
        $exitCode = $this->runConsoleAction('demo-data/fill');

        self::assertSame(ExitCode::OK, $exitCode);
        self::assertNotNull(User::findByUsername('admin'));
        self::assertSame(12, (int) Author::find()->count());
        self::assertSame(30, (int) Book::find()->count());
        self::assertSame(
            [2023 => 5, 2024 => 20, 2025 => 5],
            $this->releaseYearDistribution(),
        );
        self::assertSame(
            30,
            (int) Yii::$app->db
                ->createCommand('SELECT COUNT(DISTINCT book_id) FROM {{%book_author}}')
                ->queryScalar(),
        );
        self::assertGreaterThan(
            30,
            (int) Yii::$app->db
                ->createCommand('SELECT COUNT(*) FROM {{%book_author}}')
                ->queryScalar(),
        );
        self::assertSame($this->bookImageNames(), $this->storedImageNames());
    }

    #[TestDox('Повторный запуск команды демо-данных отклоняется без дублирования каталога')]
    public function testDemoDataFillRejectsExistingDataset(): void
    {
        self::assertSame(ExitCode::OK, $this->runConsoleAction('demo-data/fill'));
        $storedImages = $this->storedImageNames();

        $exitCode = $this->runConsoleAction('demo-data/fill');

        self::assertSame(ExitCode::DATAERR, $exitCode);
        self::assertSame(1, (int) User::find()->where(['username' => 'admin'])->count());
        self::assertSame(12, (int) Author::find()->count());
        self::assertSame(30, (int) Book::find()->count());
        self::assertSame($storedImages, $this->storedImageNames());
    }

    #[TestDox('Команда демо-данных отклоняет комплект без встроенных обложек')]
    public function testDemoDataFillRejectsMissingBundledCovers(): void
    {
        $fixtureBasePath = Yii::getAlias('@runtime/command-fixtures/missing-covers');
        FileHelper::removeDirectory($fixtureBasePath);
        FileHelper::createDirectory($fixtureBasePath);

        try {
            $exitCode = $this->runConsoleAction('demo-data/fill', [], $fixtureBasePath);

            self::assertSame(ExitCode::DATAERR, $exitCode);
            $this->assertCatalogIsEmpty();
        } finally {
            FileHelper::removeDirectory($fixtureBasePath);
        }
    }

    #[TestDox('Команда демо-данных отклоняет повреждённые встроенные обложки')]
    public function testDemoDataFillRejectsInvalidBundledCovers(): void
    {
        $fixtureBasePath = Yii::getAlias('@runtime/command-fixtures/invalid-covers');
        $coverDirectory = $fixtureBasePath . '/resources/demo-covers';
        FileHelper::removeDirectory($fixtureBasePath);
        FileHelper::createDirectory($coverDirectory);
        for ($number = 1; $number <= 4; $number++) {
            self::assertNotFalse(file_put_contents($coverDirectory . "/cover-{$number}.jpg", 'not-a-jpeg'));
        }

        try {
            $exitCode = $this->runConsoleAction('demo-data/fill', [], $fixtureBasePath);

            self::assertSame(ExitCode::DATAERR, $exitCode);
            $this->assertCatalogIsEmpty();
        } finally {
            FileHelper::removeDirectory($fixtureBasePath);
        }
    }

    #[TestDox('Команда демо-данных не изменяет каталог, если пользователь admin уже существует')]
    public function testDemoDataFillRejectsExistingAdminWithoutCatalog(): void
    {
        $admin = new User([
            'username' => 'admin',
            'password_hash' => Yii::$app->security->generatePasswordHash('existing-password'),
            'auth_key' => Yii::$app->security->generateRandomString(),
        ]);
        self::assertTrue($admin->save());

        $exitCode = $this->runConsoleAction('demo-data/fill');

        self::assertSame(ExitCode::DATAERR, $exitCode);
        self::assertSame(1, (int) User::find()->count());
        self::assertSame(0, (int) Author::find()->count());
        self::assertSame(0, (int) Book::find()->count());
    }

    #[TestDox('Отказ сохранить демо-пользователя прерывает создание каталога без частичных данных')]
    public function testDemoDataFillRollsBackWhenUserSaveIsRejected(): void
    {
        $userInsertAttempted = false;
        $handler = static function (ModelEvent $event) use (&$userInsertAttempted): void {
            $userInsertAttempted = true;
            $event->isValid = false;
        };
        Event::on(User::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);

        try {
            $exitCode = $this->runConsoleAction('demo-data/fill');
        } finally {
            Event::off(User::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        }

        self::assertTrue($userInsertAttempted);
        self::assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertCatalogHasNoPartialData();
    }

    #[TestDox('Отказ сохранить демо-автора откатывает уже созданные данные')]
    public function testDemoDataFillRollsBackWhenAuthorSaveIsRejected(): void
    {
        $authorInsertAttempts = 0;
        $handler = static function (ModelEvent $event) use (&$authorInsertAttempts): void {
            $authorInsertAttempts++;
            if ($authorInsertAttempts === 2) {
                $event->isValid = false;
            }
        };
        Event::on(Author::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);

        try {
            $exitCode = $this->runConsoleAction('demo-data/fill');
        } finally {
            Event::off(Author::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        }

        self::assertSame(2, $authorInsertAttempts);
        self::assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertCatalogHasNoPartialData();
    }

    #[TestDox('Ошибка валидации сгенерированной демо-книги откатывает каталог')]
    public function testDemoDataFillRollsBackWhenGeneratedBookValidationIsRejected(): void
    {
        $generatedBookValidationAttempted = false;
        $handler = static function (ModelEvent $event) use (&$generatedBookValidationAttempted): void {
            if (!$event->sender instanceof BookForm || !str_starts_with($event->sender->title, 'Демо-книга')) {
                return;
            }

            $generatedBookValidationAttempted = true;
            $event->isValid = false;
        };
        Event::on(BookForm::class, BookForm::EVENT_BEFORE_VALIDATE, $handler);

        try {
            $exitCode = $this->runConsoleAction('demo-data/fill');
        } finally {
            Event::off(BookForm::class, BookForm::EVENT_BEFORE_VALIDATE, $handler);
        }

        self::assertTrue($generatedBookValidationAttempted);
        self::assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertCatalogHasNoPartialData();
    }

    #[TestDox('Ошибка второй демо-книги откатывает каталог и удаляет созданные изображения')]
    public function testDemoDataFillFailureRollsBackCatalogAndRemovesImages(): void
    {
        $bookInsertCount = 0;
        $handler = static function (Event $event) use (&$bookInsertCount): void {
            $bookInsertCount++;
            if ($bookInsertCount === 2) {
                throw new \RuntimeException('Injected second demo book failure.');
            }
        };
        Event::on(Book::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);

        try {
            $exitCode = $this->runConsoleAction('demo-data/fill');
        } finally {
            Event::off(Book::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        }

        self::assertSame(2, $bookInsertCount);
        self::assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertCatalogIsEmpty();
        self::assertSame(
            0,
            (int) Yii::$app->db->createCommand('SELECT COUNT(*) FROM {{%book_author}}')->queryScalar(),
        );
        self::assertSame([], $this->storedImageNames());
    }

    /**
     * @param array<int, string> $actionParams
     */
    private function runConsoleAction(
        string $route,
        array $actionParams = [],
        ?string $basePath = null,
    ): int {
        $webBasePath = Yii::getAlias('@app');
        $webRuntimePath = Yii::getAlias('@runtime');
        $config = require dirname(__DIR__, 2) . '/config/console.php';
        $config['id'] = 'basic-console-test';
        $config['basePath'] = $basePath ?? $config['basePath'];
        $config['runtimePath'] = dirname(__DIR__, 2) . '/runtime';
        $config['components']['db'] = require dirname(__DIR__, 2) . '/config/test_db.php';
        $config['params']['bookImageStorageRoot'] = '@runtime/test-book-uploads';
        $consoleApp = new ConsoleApplication($config);
        $this->registerOutputFilter();
        $stdoutFilter = null;
        $stderrFilter = null;

        try {
            $stdoutFilter = stream_filter_append(\STDOUT, self::OUTPUT_FILTER, STREAM_FILTER_WRITE);
            if ($stdoutFilter === false) {
                throw new \RuntimeException('Unable to attach the command STDOUT filter.');
            }
            $stderrFilter = stream_filter_append(\STDERR, self::OUTPUT_FILTER, STREAM_FILTER_WRITE);
            if ($stderrFilter === false) {
                throw new \RuntimeException('Unable to attach the command STDERR filter.');
            }

            $exitCode = $consoleApp->runAction($route, $actionParams);
            self::assertIsInt($exitCode);

            return $exitCode;
        } finally {
            if (is_resource($stdoutFilter)) {
                stream_filter_remove($stdoutFilter);
            }
            if (is_resource($stderrFilter)) {
                stream_filter_remove($stderrFilter);
            }
            $consoleApp->db->close();
            Yii::$app = $this->app;
            Yii::setAlias('@app', $webBasePath);
            Yii::setAlias('@runtime', $webRuntimePath);
            restore_error_handler();
            restore_exception_handler();
        }
    }

    private function registerOutputFilter(): void
    {
        if (in_array(self::OUTPUT_FILTER, stream_get_filters(), true)) {
            return;
        }

        if (!stream_filter_register(self::OUTPUT_FILTER, CommandOutputSinkFilter::class)) {
            throw new \RuntimeException('Unable to register the command output filter.');
        }
    }

    private function assertCatalogIsEmpty(): void
    {
        self::assertSame(0, (int) User::find()->count());
        self::assertSame(0, (int) Author::find()->count());
        self::assertSame(0, (int) Book::find()->count());
    }

    private function assertCatalogHasNoPartialData(): void
    {
        $this->assertCatalogIsEmpty();
        self::assertSame(
            0,
            (int) Yii::$app->db->createCommand('SELECT COUNT(*) FROM {{%book_author}}')->queryScalar(),
        );
        self::assertSame([], $this->storedImageNames());
    }

    /**
     * @return array<int, int>
     */
    private function releaseYearDistribution(): array
    {
        $rows = Book::find()
            ->select(['release_year', 'COUNT(*) AS book_count'])
            ->groupBy(['release_year'])
            ->orderBy(['release_year' => SORT_ASC])
            ->asArray()
            ->all();

        $distribution = [];
        foreach ($rows as $row) {
            $distribution[(int) $row['release_year']] = (int) $row['book_count'];
        }

        return $distribution;
    }

    /**
     * @return string[]
     */
    private function bookImageNames(): array
    {
        $names = array_map(
            static fn (Book $book): string => basename($book->image_path),
            Book::find()->all(),
        );
        sort($names);

        return $names;
    }

    /**
     * @return string[]
     */
    private function storedImageNames(): array
    {
        $files = glob(Yii::getAlias('@runtime/test-book-uploads/*')) ?: [];
        $names = array_map('basename', $files);
        sort($names);

        return $names;
    }
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses -- Test-only filter belongs with its sole consumer.
final class CommandOutputSinkFilter extends \php_user_filter
{
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
        }

        return PSFS_PASS_ON;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
