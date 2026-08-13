<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Author $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$form = ActiveForm::begin();
?>
<?= $form->field($model, 'full_name')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
<div class="form-group">
    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
</div>
<?php ActiveForm::end(); ?>
