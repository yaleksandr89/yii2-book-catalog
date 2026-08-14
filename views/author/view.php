<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Author $model */

use yii\bootstrap5\Html;

$this->title = $model->full_name;
$this->params['breadcrumbs'] = [['label' => 'Авторы', 'url' => ['index']], $this->title];
?>
<div class="author-view">
    <h1><?= Html::encode($model->full_name) ?></h1>
    <?= Html::a('Подписаться', ['/subscription/create', 'authorId' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
    <?php if (!Yii::$app->user->isGuest): ?>
        <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], ['class' => 'btn btn-outline-danger', 'data' => ['method' => 'post', 'confirm' => 'Удалить автора?']]) ?>
    <?php endif; ?>
</div>
