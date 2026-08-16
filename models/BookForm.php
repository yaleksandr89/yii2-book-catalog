<?php

declare(strict_types=1);

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

final class BookForm extends Model
{
    public const string SCENARIO_CREATE = 'create';
    public const string SCENARIO_UPDATE = 'update';

    public string $title = '';
    public int|string|null $releaseYear = null;
    public string $description = '';
    public string $isbn = '';
    /** @var array<array-key, mixed>|string|null */
    public array|string|null $authorIds = [];
    public ?UploadedFile $image = null;

    public function rules(): array
    {
        return [
            [['title', 'description', 'isbn'], 'trim'],
            [['title', 'releaseYear', 'description', 'isbn', 'authorIds'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['isbn'], 'string', 'max' => 32],
            [['releaseYear'], 'integer', 'min' => 1000, 'max' => 9999],
            [['authorIds'], 'filter', 'filter' => [$this, 'normalizeAuthorIds'], 'skipOnEmpty' => false],
            [['authorIds'], 'each', 'rule' => ['integer']],
            [['authorIds'], 'validateAuthorIds', 'skipOnError' => true],
            [
                ['image'],
                'file',
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'checkExtensionByMimeType' => true,
                'maxSize' => 5 * 1024 * 1024,
                'skipOnEmpty' => false,
                'on' => self::SCENARIO_CREATE,
            ],
            [
                ['image'],
                'file',
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'checkExtensionByMimeType' => true,
                'maxSize' => 5 * 1024 * 1024,
                'skipOnEmpty' => true,
                'on' => self::SCENARIO_UPDATE,
            ],
        ];
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $attributes = [
            'title',
            'releaseYear',
            'description',
            'isbn',
            'authorIds',
            '!image',
        ];

        $scenarios[self::SCENARIO_CREATE] = $attributes;
        $scenarios[self::SCENARIO_UPDATE] = $attributes;

        return $scenarios;
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Название',
            'releaseYear' => 'Год выпуска',
            'description' => 'Описание',
            'isbn' => 'ISBN',
            'authorIds' => 'Авторы',
            'image' => 'Обложка',
        ];
    }

    public static function forCreate(): self
    {
        $form = new self();
        $form->scenario = self::SCENARIO_CREATE;

        return $form;
    }

    public static function forUpdate(Book $book): self
    {
        $form = new self([
            'title' => $book->title,
            'releaseYear' => $book->release_year,
            'description' => $book->description,
            'isbn' => $book->isbn,
            'authorIds' => array_map(
                static fn (Author $author): int => $author->id,
                $book->authors,
            ),
        ]);
        $form->scenario = self::SCENARIO_UPDATE;

        return $form;
    }

    /**
     * @return array<int>
     */
    public function getNormalizedAuthorIds(): array
    {
        if (!is_array($this->authorIds)) {
            return [];
        }

        return array_values(array_map('intval', $this->authorIds));
    }

    public function normalizeAuthorIds(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $normalized = array_map(
            static fn (mixed $authorId): mixed => is_string($authorId) ? trim($authorId) : $authorId,
            $value,
        );

        return array_values(array_unique($normalized, SORT_REGULAR));
    }

    public function validateAuthorIds(string $attribute): void
    {
        $authorIds = array_values(
            array_unique($this->getNormalizedAuthorIds())
        );
        $existingCount = (int) Author::find()->where(['id' => $authorIds])->count();
        if ($existingCount !== count($authorIds)) {
            $this->addError($attribute, 'Выбран неизвестный автор.');
        }
    }
}
