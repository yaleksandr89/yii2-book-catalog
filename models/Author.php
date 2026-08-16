<?php

declare(strict_types=1);

namespace app\models;

use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $full_name
 * @property-read Book[] $books
 */
final class Author extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%author}}';
    }

    public function rules(): array
    {
        return [
            ['full_name', 'trim'],
            ['full_name', 'required'],
            ['full_name', 'string', 'max' => 255],
        ];
    }

    /**
     * @return ActiveQuery<Book>
     * @throws InvalidConfigException
     */
    public function getBooks(): ActiveQuery
    {
        return $this->hasMany(Book::class, ['id' => 'book_id'])
            ->viaTable('{{%book_author}}', ['author_id' => 'id'])
            ->orderBy(['title' => SORT_ASC, 'id' => SORT_ASC]);
    }
}
