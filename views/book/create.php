<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\BookForm $form */
/** @var app\models\Author[] $authors */

use yii\bootstrap5\Html;

$this->title = 'Добавить книгу';
$this->params['breadcrumbs'] = [['label' => 'Книги', 'url' => ['index']], $this->title];
?>
<div class="book-create">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', ['form' => $form, 'authors' => $authors]) ?>
</div>
