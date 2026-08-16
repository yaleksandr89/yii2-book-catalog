<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Author[] $authors */

use yii\bootstrap5\Html;

$this->title = 'Авторы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="author-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php if (!Yii::$app->user->isGuest) : ?>
            <?= Html::a('Добавить автора', ['create'], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
    </div>
    <?php if ($authors === []) : ?>
        <p class="text-body-secondary">Авторов пока нет.</p>
    <?php else : ?>
        <div class="list-group">
            <?php foreach ($authors as $author) : ?>
                <?= Html::a(
                    Html::encode($author->full_name),
                    ['view', 'id' => $author->id],
                    ['class' => 'list-group-item list-group-item-action'],
                ) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
