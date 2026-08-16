<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\TopAuthorsQuery;
use app\models\TopAuthorsReportForm;
use yii\web\Controller;

final class ReportController extends Controller
{
    public function actionTopAuthors(): string
    {
        $form = new TopAuthorsReportForm();
        $form->load($this->request->get());
        $authors = $form->validate()
            ? new TopAuthorsQuery()->findByYear((int) $form->year)
            : [];

        return $this->render('top-authors', ['form' => $form, 'authors' => $authors]);
    }
}
