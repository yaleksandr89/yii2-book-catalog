<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\models\Author;
use app\models\Book;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yii;

final class BookRelationTest extends TestCase
{
    #[TestDox('Книга возвращает связанных авторов, а автор — связанные книги')]
    public function testBookAndAuthorRelationsWorkBothWays(): void
    {
        [$book, $author] = $this->createRelatedRecords();

        self::assertSame([$author->id], array_column($book->authors, 'id'));
        self::assertSame([$book->id], array_column($author->books, 'id'));
    }

    #[TestDox('Запрос списка книг заранее загружает авторов без N+1')]
    public function testBookListQueryEagerLoadsAuthors(): void
    {
        $this->createRelatedRecords();

        $books = Book::find()
            ->with('authors')
            ->orderBy(['title' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        self::assertCount(1, $books);
        self::assertTrue($books[0]->isRelationPopulated('authors'));
        self::assertCount(1, $books[0]->authors);
    }

    /**
     * @return array{Book, Author}
     */
    private function createRelatedRecords(): array
    {
        $author = new Author(['full_name' => 'Связанный Автор']);
        self::assertTrue($author->save());
        $book = new Book([
            'title' => 'Связанная книга',
            'release_year' => 2020,
            'description' => 'Описание.',
            'isbn' => '978-5-00-000001-7',
            'image_path' => 'uploads/books/' . str_repeat('a', 32) . '.png',
        ]);
        self::assertTrue($book->save());
        Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => $author->id,
        ])->execute();

        return [$book, $author];
    }
}
