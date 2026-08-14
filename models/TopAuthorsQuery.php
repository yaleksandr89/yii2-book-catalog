<?php

declare(strict_types=1);

namespace app\models;

use yii\db\Expression;
use yii\db\Query;

final class TopAuthorsQuery
{
    /**
     * @return list<array{author_id: int, full_name: string, book_count: int}>
     */
    public function findByYear(int $year): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = new Query()
            ->select([
                'author_id' => '[[author]].[[id]]',
                'full_name' => '[[author]].[[full_name]]',
                'book_count' => new Expression('COUNT([[book]].[[id]])'),
            ])
            ->from(['author' => '{{%author}}'])
            ->innerJoin(['book_author' => '{{%book_author}}'], '[[book_author]].[[author_id]] = [[author]].[[id]]')
            ->innerJoin(['book' => '{{%book}}'], '[[book]].[[id]] = [[book_author]].[[book_id]]')
            ->where(['book.release_year' => $year])
            ->groupBy(['author.id', 'author.full_name'])
            ->orderBy(['book_count' => SORT_DESC, 'author.full_name' => SORT_ASC, 'author.id' => SORT_ASC])
            ->limit(10)
            ->all();

        return array_map(
            static fn(array $row): array => [
                'author_id' => (int) $row['author_id'],
                'full_name' => (string) $row['full_name'],
                'book_count' => (int) $row['book_count'],
            ],
            $rows,
        );
    }
}
