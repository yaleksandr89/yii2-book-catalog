<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;

final class SubscriptionForm extends Model
{
    public string $phone = '';

    public function __construct(
        private readonly int $authorId,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['phone'], 'filter', 'filter' => [$this, 'normalizePhone']],
            [['phone'], 'required'],
            [['phone'], 'string', 'max' => 15],
            [
                ['phone'],
                'match',
                'pattern' => '/^[1-9][0-9]{9,14}$/',
                'message' => 'Введите номер из 10–15 цифр, начиная не с нуля.',
            ],
            [['phone'], 'validateDuplicate'],
        ];
    }

    public function attributeLabels(): array
    {
        return ['phone' => 'Телефон'];
    }

    public function normalizePhone(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $normalized = str_replace([' ', '-', '(', ')'], '', trim($value));

        return str_starts_with($normalized, '+') ? substr($normalized, 1) : $normalized;
    }

    public function validateDuplicate(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $exists = Subscription::find()
            ->where(['author_id' => $this->authorId, 'phone' => $this->phone])
            ->exists();
        if ($exists) {
            $this->addError($attribute, 'Этот номер уже подписан на автора.');
        }
    }
}
