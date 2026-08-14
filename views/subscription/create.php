<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Author $author */
/** @var app\models\SubscriptionForm $form */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Подписаться';
$this->params['breadcrumbs'] = [
    ['label' => 'Авторы', 'url' => ['/author/index']],
    ['label' => $author->full_name, 'url' => ['/author/view', 'id' => $author->id]],
    $this->title,
];
?>
<div class="subscription-create">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>Оставьте телефон, чтобы подписаться на автора «<?= Html::encode($author->full_name) ?>».</p>

    <?php $activeForm = ActiveForm::begin() ?>
        <?= $activeForm->field($form, 'phone')->textInput(['autocomplete' => 'tel', 'inputmode' => 'tel']) ?>
        <?= Html::submitButton('Подписаться', ['class' => 'btn btn-primary']) ?>
    <?php ActiveForm::end() ?>
</div>
