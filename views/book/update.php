<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\BookForm $form */
/** @var app\models\Book $book */
/** @var app\models\Author[] $authors */

use yii\bootstrap5\Html;

$this->title = 'Редактировать «' . $book->title . '»';
$this->params['breadcrumbs'] = [
    ['label' => 'Книги', 'url' => ['index']],
    ['label' => $book->title, 'url' => ['view', 'id' => $book->id]],
    'Редактирование',
];
?>
<div class="book-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="mb-3">
        <?= Html::img(
            Yii::getAlias('@web/' . $book->image_path),
            ['alt' => 'Текущая обложка', 'class' => 'img-thumbnail', 'style' => 'max-width: 160px; max-height: 220px'],
        ) ?>
    </div>
    <?= $this->render('_form', ['form' => $form, 'authors' => $authors]) ?>
</div>
