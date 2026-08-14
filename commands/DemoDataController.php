<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Author;
use app\models\Book;
use app\models\BookForm;
use app\services\BookService;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\web\UploadedFile;

final class DemoDataController extends Controller
{
    private const string DESCRIPTION = 'Демонстрационная книга для проверки каталога и отчёта.';

    /**
     * @return array<int, UploadedFile>|null
     */
    private function demoCovers(): ?array
    {
        $covers = [];
        for ($number = 1; $number <= 4; $number++) {
            $path = Yii::getAlias("@runtime/demo-covers/cover-{$number}.jpg");
            if (!is_file($path) || !is_readable($path)) {
                return null;
            }

            $cover = new UploadedFile([
                'name' => basename($path),
                'tempName' => $path,
                'type' => 'image/jpeg',
                'size' => filesize($path),
                'error' => UPLOAD_ERR_OK,
            ]);
            $form = BookForm::forCreate();
            $form->image = $cover;
            if (!$form->validate(['image'])) {
                return null;
            }

            $covers[] = $cover;
        }

        return $covers;
    }

    public function actionFill(): int
    {
        $covers = $this->demoCovers();
        if ($covers === null) {
            $this->stderr("Не найдены или недействительны JPEG-обложки: @runtime/demo-covers/cover-1.jpg, @runtime/demo-covers/cover-2.jpg, @runtime/demo-covers/cover-3.jpg, @runtime/demo-covers/cover-4.jpg.\n");

            return ExitCode::DATAERR;
        }

        if (
            Author::find()->where(['full_name' => 'Демо Автор 01'])->exists()
            || Book::find()->where(['title' => 'Демо-книга 01'])->exists()
        ) {
            $this->stderr("Демо-данные уже существуют.\n");

            return ExitCode::DATAERR;
        }

        $storageRoot = Yii::getAlias((string) Yii::$app->params['bookImageStorageRoot']);
        $service = new BookService($storageRoot);

        $createdImages = [];
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $authorIds = [];
            for ($number = 1; $number <= 12; $number++) {
                $author = new Author(['full_name' => sprintf('Демо Автор %02d', $number)]);
                if (!$author->save()) {
                    throw new \RuntimeException('Не удалось сохранить демо-автора.');
                }
                $authorIds[] = $author->id;
            }

            for ($index = 0; $index < 30; $index++) {
                $authorIndexes = [$index % 12];
                if ($index % 2 === 0) {
                    $authorIndexes[] = ($index + 3) % 12;
                }
                if ($index % 5 === 0) {
                    $authorIndexes[] = ($index + 7) % 12;
                }

                $form = new BookForm([
                    'scenario' => BookForm::SCENARIO_CREATE,
                    'title' => sprintf('Демо-книга %02d', $index + 1),
                    'releaseYear' => $index < 20 ? 2024 : ($index < 25 ? 2023 : 2025),
                    'description' => self::DESCRIPTION,
                    'isbn' => sprintf('978-5-00-%06d-%d', $index + 1, $index % 10),
                    'authorIds' => array_map(
                        static fn (int $authorIndex): int => $authorIds[$authorIndex],
                        array_values(array_unique($authorIndexes)),
                    ),
                    'image' => $covers[$index % 4],
                ]);
                if (!$form->validate()) {
                    throw new \RuntimeException('Не удалось проверить демо-книгу.');
                }

                $createdImages[] = $service->create($form)->image_path;
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            $this->removeCreatedImages($createdImages, $storageRoot);
            $this->stderr("Не удалось создать демо-данные.\n");

            return ExitCode::DATAERR;
        }

        $this->stdout("Demo data created: 12 authors, 30 books.\n");

        return ExitCode::OK;
    }

    /**
     * @param array<int, string> $imagePaths
     */
    private function removeCreatedImages(array $imagePaths, string $storageRoot): void
    {
        foreach ($imagePaths as $imagePath) {
            $path = $storageRoot . DIRECTORY_SEPARATOR . basename($imagePath);
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
