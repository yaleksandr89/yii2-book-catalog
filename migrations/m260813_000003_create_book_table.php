<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260813_000003_create_book_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%book}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'release_year' => $this->integer()->notNull(),
            'description' => $this->text()->notNull(),
            'isbn' => $this->string(32)->notNull(),
            'image_path' => $this->string()->notNull(),
        ]);

        $this->createIndex('idx-book-release-year', '{{%book}}', 'release_year');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%book}}');
    }
}
