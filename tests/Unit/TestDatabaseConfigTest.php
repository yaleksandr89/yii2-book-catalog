<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii\db\Connection;

final class TestDatabaseConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $environment = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'DB_HOST',
            'DB_PORT',
            'MYSQL_DATABASE',
            'MYSQL_TEST_DATABASE',
            'MYSQL_USER',
            'MYSQL_PASSWORD',
            'COOKIE_VALIDATION_KEY',
            'YII2_TEST_DB_GUARD',
        ] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : "$name=$value");
        }

        parent::tearDown();
    }

    #[TestDox('Тестовая БД не может совпадать с основной, в том числе без учёта регистра')]
    public function testRejectsSameNameIgnoringCase(): void
    {
        putenv('MYSQL_DATABASE=book_catalog');
        putenv('MYSQL_TEST_DATABASE=BOOK_CATALOG');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MYSQL_TEST_DATABASE must differ');

        require dirname(__DIR__, 2) . '/config/test_db.php';
    }

    #[TestDox('Явное имя тестовой БД сравнивается с именем основной БД по умолчанию')]
    public function testRejectsDefaultApplicationName(): void
    {
        putenv('MYSQL_TEST_DATABASE=yii2_book_catalog');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MYSQL_TEST_DATABASE must differ');

        require dirname(__DIR__, 2) . '/config/test_db.php';
    }

    #[DataProvider('unsafeNames')]
    #[TestDox('Пустое, небезопасное или системное имя тестовой БД отклоняется')]
    public function testRejectsUnsafeName(string $name): void
    {
        putenv("MYSQL_TEST_DATABASE=$name");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MYSQL_TEST_DATABASE must be a safe');

        require dirname(__DIR__, 2) . '/config/test_db.php';
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeNames(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => [' '];
        yield 'unsafe punctuation' => ['catalog`test'];
        yield 'DSN delimiter' => ['catalog;test'];
        yield 'overlong' => [str_repeat('a', 65)];
        yield 'system schema' => ['MYSQL'];
    }

    #[TestDox('Отдельная тестовая БД использует локальный адрес Compose и заданные параметры подключения')]
    public function testBuildsDistinctTestConfiguration(): void
    {
        putenv('MYSQL_DATABASE=book_catalog');
        putenv('MYSQL_TEST_DATABASE=book_catalog_test');
        putenv('DB_HOST=mysql');
        putenv('DB_PORT=3306');
        putenv('MYSQL_USER=unit_test_user');
        putenv('MYSQL_PASSWORD=unit-test-password');

        self::assertSame([
            'class' => Connection::class,
            'dsn' => 'mysql:host=mysql;port=3306;dbname=book_catalog_test',
            'username' => 'unit_test_user',
            'password' => 'unit-test-password',
            'charset' => 'utf8mb4',
        ], require dirname(__DIR__, 2) . '/config/test_db.php');
    }

    #[DataProvider('unsupportedEndpoints')]
    #[TestDox('Нелокальный или некорректный адрес тестовой БД отклоняется до подключения')]
    public function testRejectsUnsupportedEndpoint(string $host, string $port): void
    {
        putenv("DB_HOST=$host");
        putenv("DB_PORT=$port");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('require the local Compose MySQL endpoint');

        require dirname(__DIR__, 2) . '/config/test_db.php';
    }

    /** @return iterable<string, array{string, string}> */
    public static function unsupportedEndpoints(): iterable
    {
        yield 'nonlocal host' => ['isolated-mysql', '3306'];
        yield 'nonstandard port' => ['mysql', '3307'];
        yield 'malformed host' => ['mysql;dbname=primary', '3306'];
        yield 'malformed port' => ['mysql', '3306;dbname=primary'];
    }

    #[TestDox('Неоднозначное имя основной БД блокирует тестовую конфигурацию')]
    public function testRejectsAmbiguousApplicationName(): void
    {
        putenv('MYSQL_DATABASE=book_catalog;dbname=yii2_book_catalog_test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MYSQL_DATABASE must specify an unambiguous application schema');

        require dirname(__DIR__, 2) . '/config/test_db.php';
    }

    #[DataProvider('unguardedEntryPoints')]
    #[TestDox('Тестовые конфигурации приложения требуют предварительной проверки Make')]
    public function testRejectsUnguardedApplicationConfig(string $file, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        require dirname(__DIR__, 2) . '/config/' . $file;
    }

    /** @return iterable<string, array{string, string}> */
    public static function unguardedEntryPoints(): iterable
    {
        yield 'integration' => ['test.php', 'Run integration tests through a guarded Make target'];
        yield 'console' => ['console.php', 'Run test migrations through make test-db-migrate'];
    }

    #[TestDox('После проверки Make приложения используют тестовую схему в итоговой конфигурации')]
    public function testGuardedApplicationConfigsUseTestSchema(): void
    {
        putenv('YII2_TEST_DB_GUARD=1');
        putenv('COOKIE_VALIDATION_KEY=unit-test-cookie-key');
        putenv('MYSQL_DATABASE=book_catalog');
        putenv('MYSQL_TEST_DATABASE=book_catalog_test');

        foreach (['test.php', 'console.php'] as $file) {
            $config = require dirname(__DIR__, 2) . '/config/' . $file;
            self::assertSame('mysql:host=mysql;port=3306;dbname=book_catalog_test', $config['components']['db']['dsn'], $file);
        }
    }

    #[TestDox('При отсутствии переменной используется отдельное имя тестовой БД по умолчанию')]
    public function testDefaultsOnlyWhenNameIsAbsent(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/test_db.php';

        self::assertSame('mysql:host=mysql;port=3306;dbname=yii2_book_catalog_test', $config['dsn']);
    }
}
