<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

final class TopAuthorsReportForm extends Model
{
    public int|string|null $year = null;

    public function __construct(array $config = [])
    {
        $this->year = (int) date('Y');
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['year'], 'required'],
            [['year'], 'integer', 'min' => 1000, 'max' => 9999],
        ];
    }

    public function attributeLabels(): array
    {
        return ['year' => 'Год'];
    }
}
