<?php

declare(strict_types=1);

namespace tests\integration;

use app\models\Author;
use app\models\Book;
use app\models\TopAuthorsQuery;
use PHPUnit\Framework\Attributes\TestDox;
use tests\TestCase;
use Yii;

final class ReportTest extends TestCase
{
    private int $_bookNumber = 0;

    #[TestDox('Рейтинг учитывает только выбранный год и сортирует авторов по числу книг')]
    public function testRankingUsesSelectedYearAndOrdersByBookCount(): void
    {
        $leader = $this->createAuthor('Лидер');
        $second = $this->createAuthor('Второй');
        $excluded = $this->createAuthor('Автор другого года');
        $this->createBooks($leader, 3, 2024);
        $this->createBooks($second, 2, 2024);
        $this->createBooks($excluded, 5, 2023);

        $authors = (new TopAuthorsQuery())->findByYear(2024);

        self::assertSame([$leader->id, $second->id], array_column($authors, 'author_id'));
        self::assertSame([3, 2], array_column($authors, 'book_count'));
    }

    #[TestDox('Рейтинг возвращает не более десяти авторов и детерминированно сортирует равные результаты')]
    public function testRankingLimitsResultsAndOrdersTiesByNameThenId(): void
    {
        $authorIds = [];
        foreach (['Alpha', 'Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo', 'Foxtrot', 'Golf', 'Hotel', 'India', 'Juliet', 'Kilo'] as $fullName) {
            $author = $this->createAuthor($fullName);
            $authorIds[] = $author->id;
            $this->createBooks($author, 1, 2024);
        }

        $authors = (new TopAuthorsQuery())->findByYear(2024);

        self::assertCount(10, $authors);
        self::assertSame(array_slice($authorIds, 0, 10), array_column($authors, 'author_id'));
    }

    #[TestDox('Гость может открыть страницу топа авторов')]
    public function testGuestCanOpenTopAuthorsReport(): void
    {
        self::assertIsString($this->app->runAction('report/top-authors'));
    }

    private function createAuthor(string $fullName): Author
    {
        $author = new Author(['full_name' => $fullName]);
        self::assertTrue($author->save());

        return $author;
    }

    private function createBooks(Author $author, int $count, int $year): void
    {
        for ($index = 0; $index < $count; $index++) {
            $book = new Book([
                'title' => 'Книга отчёта ' . ++$this->_bookNumber,
                'release_year' => $year,
                'description' => 'Описание книги для отчёта.',
                'isbn' => '978-5-00-' . str_pad((string) $this->_bookNumber, 7, '0', STR_PAD_LEFT),
                'image_path' => 'uploads/books/report-' . $this->_bookNumber . '.png',
            ]);
            self::assertTrue($book->save());
            Yii::$app->db->createCommand()->insert('{{%book_author}}', [
                'book_id' => $book->id,
                'author_id' => $author->id,
            ])->execute();
        }
    }
}
