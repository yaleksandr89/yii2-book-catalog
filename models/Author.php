<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $full_name
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
}
