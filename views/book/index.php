<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Book[] $books */
/** @var yii\data\Pagination $pagination */

use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;

$this->title = 'Книги';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php if (!Yii::$app->user->isGuest) : ?>
            <?= Html::a('Добавить книгу', ['create'], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
    </div>
    <?php if ($books === []) : ?>
        <p class="text-body-secondary">Книг пока нет.</p>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Обложка</th>
                        <th scope="col">Название</th>
                        <th scope="col">Год</th>
                        <th scope="col">Авторы</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book) : ?>
                        <tr>
                            <td>
                                <?= Html::img(
                                    Yii::getAlias('@web/' . $book->image_path),
                                    [
                                        'alt' => 'Обложка «' . $book->title . '»',
                                        'class' => 'img-thumbnail',
                                        'style' => 'max-width: 90px; max-height: 120px',
                                    ],
                                ) ?>
                            </td>
                            <td><?= Html::a(Html::encode($book->title), ['view', 'id' => $book->id]) ?></td>
                            <td><?= Html::encode((string) $book->release_year) ?></td>
                            <td><?= Html::encode(implode(', ', array_column($book->authors, 'full_name'))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= LinkPager::widget(['pagination' => $pagination]) ?>
    <?php endif; ?>
</div>
