<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Book $model */

use yii\bootstrap5\Html;

$this->title = $model->title;
$this->params['breadcrumbs'] = [['label' => 'Книги', 'url' => ['index']], $this->title];
?>
<div class="book-view">
    <h1><?= Html::encode($model->title) ?></h1>
    <div class="row g-4 mb-4">
        <div class="col-md-4 col-lg-3">
            <?= Html::img(
                Yii::getAlias('@web/' . $model->image_path),
                ['alt' => 'Обложка «' . $model->title . '»', 'class' => 'img-fluid rounded'],
            ) ?>
        </div>
        <div class="col-md-8 col-lg-9">
            <dl class="row">
                <dt class="col-sm-3">Год выпуска</dt>
                <dd class="col-sm-9"><?= Html::encode((string) $model->release_year) ?></dd>
                <dt class="col-sm-3">ISBN</dt>
                <dd class="col-sm-9"><?= Html::encode($model->isbn) ?></dd>
                <dt class="col-sm-3">Авторы</dt>
                <dd class="col-sm-9"><?= Html::encode(implode(', ', array_column($model->authors, 'full_name'))) ?></dd>
                <dt class="col-sm-3">Описание</dt>
                <dd class="col-sm-9"><?= nl2br(Html::encode($model->description)) ?></dd>
            </dl>
        </div>
    </div>
    <?php if (!Yii::$app->user->isGuest) : ?>
        <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(
            'Удалить',
            ['delete', 'id' => $model->id],
            ['class' => 'btn btn-outline-danger', 'data' => ['method' => 'post', 'confirm' => 'Удалить книгу?']],
        ) ?>
    <?php endif; ?>
</div>
