<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260814_000005_create_subscription_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%subscription}}', [
            'id' => $this->primaryKey(),
            'author_id' => $this->integer()->notNull(),
            'phone' => $this->string(15)->notNull(),
        ]);

        $this->createIndex(
            'uq-subscription-author-id-phone',
            '{{%subscription}}',
            ['author_id', 'phone'],
            true,
        );
        $this->addForeignKey(
            'fk-subscription-author-id',
            '{{%subscription}}',
            'author_id',
            '{{%author}}',
            'id',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-subscription-author-id', '{{%subscription}}');
        $this->dropTable('{{%subscription}}');
    }
}
