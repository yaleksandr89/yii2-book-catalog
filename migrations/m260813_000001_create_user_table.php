<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260813_000001_create_user_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull(),
            'password_hash' => $this->string()->notNull(),
            'auth_key' => $this->string()->notNull(),
        ]);
        $this->createIndex('idx-user-username', '{{%user}}', 'username', true);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%user}}');
    }
}
