<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\BookForm $form */
/** @var app\models\Author[] $authors */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;

$activeForm = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);
?>
<?= $activeForm->field($form, 'title')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
<?= $activeForm->field($form, 'releaseYear')->input('number', ['min' => 1000, 'max' => 9999]) ?>
<?= $activeForm->field($form, 'description')->textarea(['rows' => 8]) ?>
<?= $activeForm->field($form, 'isbn')->textInput(['maxlength' => true]) ?>
<?= $activeForm->field($form, 'authorIds')->listBox(
    ArrayHelper::map($authors, 'id', 'full_name'),
    ['multiple' => true, 'size' => min(10, max(3, count($authors)))],
) ?>
<?= $activeForm->field($form, 'image')->fileInput(['accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp']) ?>
<?php if ($form->scenario === $form::SCENARIO_UPDATE): ?>
    <p class="form-text">Если не выбирать новый файл, текущая обложка сохранится.</p>
<?php endif; ?>
<div class="form-group">
    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
</div>
<?php ActiveForm::end(); ?>
