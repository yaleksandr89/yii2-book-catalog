<?php

declare(strict_types=1);

namespace tests\integration;

use app\models\Author;
use PHPUnit\Framework\Attributes\TestDox;
use tests\TestCase;

final class AuthorTest extends TestCase
{
    #[TestDox('Корректный автор сохраняется')]
    public function testValidAuthorSaves(): void
    {
        $author = new Author(['full_name' => 'Лев Толстой']);

        self::assertTrue($author->save());
        self::assertNotNull(Author::findOne($author->id));
    }

    #[TestDox('Пустое имя автора отклоняется')]
    public function testBlankOrWhitespaceFullNameIsRejected(): void
    {
        $author = new Author(['full_name' => '   ']);

        self::assertFalse($author->validate());
        self::assertNotEmpty($author->getErrors('full_name'));
    }

    #[TestDox('Имя автора длиннее 255 символов отклоняется')]
    public function testFullNameLongerThan255CharactersIsRejected(): void
    {
        $author = new Author(['full_name' => str_repeat('А', 256)]);

        self::assertFalse($author->validate());
        self::assertNotEmpty($author->getErrors('full_name'));
    }
}
