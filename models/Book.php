<?php

declare(strict_types=1);

namespace app\models;

use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $title
 * @property int $release_year
 * @property string $description
 * @property string $isbn
 * @property string $image_path
 * @property-read Author[] $authors
 */
final class Book extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%book}}';
    }

    public function rules(): array
    {
        return [
            [['title', 'release_year', 'description', 'isbn', 'image_path'], 'required'],
            [['release_year'], 'integer'],
            [['description'], 'string'],
            [['title', 'image_path'], 'string', 'max' => 255],
            [['isbn'], 'string', 'max' => 32],
        ];
    }

    /**
     * @return ActiveQuery<Author>
     * @throws InvalidConfigException
     */
    public function getAuthors(): ActiveQuery
    {
        return $this->hasMany(Author::class, ['id' => 'author_id'])
            ->viaTable('{{%book_author}}', ['book_id' => 'id'])
            ->orderBy(['full_name' => SORT_ASC]);
    }
}
