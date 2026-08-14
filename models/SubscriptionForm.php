<?php

declare(strict_types=1);

namespace app\models;

use LogicException;
use yii\base\Model;

final class SubscriptionForm extends Model
{
    public string $phone = '';

    private ?string $normalizedPhone = null;

    public function __construct(
        private readonly int $authorId,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['phone'], 'required', 'message' => 'Введите телефон.'],
            [['phone'], 'validatePhone', 'skipOnError' => true],
            [['phone'], 'validateDuplicate', 'skipOnError' => true],
        ];
    }

    public function attributeLabels(): array
    {
        return ['phone' => 'Телефон'];
    }

    public function getNormalizedPhone(): string
    {
        if ($this->normalizedPhone === null) {
            throw new LogicException('Телефон не был успешно провалидирован.');
        }

        return $this->normalizedPhone;
    }

    public function validatePhone(string $attribute): void
    {
        $normalized = $this->normalizePhone($this->phone);

        if (preg_match('/^[1-9]\d{9,14}$/', $normalized) !== 1) {
            $this->addError(
                $attribute,
                'Введите номер из 10–15 цифр, начиная не с нуля.',
            );

            return;
        }

        $this->normalizedPhone = $normalized;
    }

    public function validateDuplicate(string $attribute): void
    {
        $normalizedPhone = $this->getNormalizedPhone();

        $exists = Subscription::find()
            ->where([
                'author_id' => $this->authorId,
                'phone' => $normalizedPhone,
            ])
            ->exists();

        if ($exists) {
            $this->addError($attribute, 'Этот номер уже подписан на автора.');
        }
    }

    private function normalizePhone(string $value): string
    {
        $normalized = str_replace(
            [' ', '-', '(', ')'],
            '',
            trim($value),
        );

        return str_starts_with($normalized, '+')
            ? substr($normalized, 1)
            : $normalized;
    }
}
