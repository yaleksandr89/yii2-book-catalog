<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\TopAuthorsReportForm $form */
/** @var list<array{author_id: int, full_name: string, book_count: int}> $authors */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Топ авторов';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="report-top-authors">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $activeForm = ActiveForm::begin(['method' => 'get', 'action' => ['top-authors']]) ?>
        <div class="row align-items-end">
            <div class="col-sm-4 col-md-3">
                <?= $activeForm->field($form, 'year')->input('number', ['min' => 1000, 'max' => 9999]) ?>
            </div>
            <div class="col-sm-8 col-md-9 mb-3">
                <?= Html::submitButton('Показать', ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    <?php ActiveForm::end() ?>

    <?php if (!$form->hasErrors() && $authors === []): ?>
        <p class="text-body-secondary">За выбранный год книги не найдены.</p>
    <?php elseif ($authors !== []): ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Место</th>
                        <th scope="col">Автор</th>
                        <th scope="col">Количество книг</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($authors as $position => $author): ?>
                        <tr>
                            <td><?= Html::encode((string) ($position + 1)) ?></td>
                            <td><?= Html::a(Html::encode($author['full_name']), ['/author/view', 'id' => $author['author_id']]) ?></td>
                            <td><?= Html::encode((string) $author['book_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
