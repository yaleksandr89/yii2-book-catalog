<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260813_000004_create_book_author_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%book_author}}', [
            'book_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk-book-author', '{{%book_author}}', ['book_id', 'author_id']);
        $this->createIndex('idx-book-author-author-id', '{{%book_author}}', 'author_id');
        $this->addForeignKey(
            'fk-book-author-book-id',
            '{{%book_author}}',
            'book_id',
            '{{%book}}',
            'id',
            'CASCADE',
        );
        $this->addForeignKey(
            'fk-book-author-author-id',
            '{{%book_author}}',
            'author_id',
            '{{%author}}',
            'id',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-book-author-author-id', '{{%book_author}}');
        $this->dropForeignKey('fk-book-author-book-id', '{{%book_author}}');
        $this->dropTable('{{%book_author}}');
    }
}
