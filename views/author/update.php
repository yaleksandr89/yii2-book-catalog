<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Author $model */

use yii\bootstrap5\Html;

$this->title = 'Редактировать автора';
$this->params['breadcrumbs'] = [['label' => 'Авторы', 'url' => ['index']], ['label' => $model->full_name, 'url' => ['view', 'id' => $model->id]], $this->title];
?>
<div class="author-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
