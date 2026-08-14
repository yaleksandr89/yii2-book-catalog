<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Author;
use app\models\Book;
use app\models\BookForm;
use app\services\BookService;
use Throwable;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

final class BookController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['create', 'update', 'delete'],
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                ]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $query = Book::find()->orderBy(['title' => SORT_ASC, 'id' => SORT_ASC]);
        $pagination = new Pagination([
            'totalCount' => (int) $query->count(),
            'pageSize' => 10,
        ]);
        $books = $query
            ->with('authors')
            ->offset($pagination->getOffset())
            ->limit($pagination->getLimit())
            ->all();

        return $this->render('index', ['books' => $books, 'pagination' => $pagination]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * @throws Throwable
     */
    public function actionCreate(): Response|string
    {
        $form = BookForm::forCreate();

        if ($form->load($this->request->post())) {
            $form->image = UploadedFile::getInstance($form, 'image');
            if ($form->validate()) {
                $book = $this->bookService()->create($form);

                return $this->redirect(['view', 'id' => $book->id]);
            }
        }

        return $this->render('create', ['form' => $form, 'authors' => $this->authorOptions()]);
    }

    /**
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionUpdate(int $id): Response|string
    {
        $book = $this->findModel($id);
        $form = BookForm::forUpdate($book);

        if ($form->load($this->request->post())) {
            $form->image = UploadedFile::getInstance($form, 'image');
            if ($form->validate()) {
                $this->bookService()->update($book, $form);

                return $this->redirect(['view', 'id' => $book->id]);
            }
        }

        return $this->render('update', [
            'form' => $form,
            'book' => $book,
            'authors' => $this->authorOptions(),
        ]);
    }

    /**
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionDelete(int $id): Response
    {
        $this->bookService()->delete($this->findModel($id));

        return $this->redirect(['index']);
    }

    /**
     * @throws NotFoundHttpException
     */
    private function findModel(int $id): Book
    {
        $book = Book::find()->with('authors')->where(['id' => $id])->one();
        if ($book === null) {
            throw new NotFoundHttpException('Книга не найдена.');
        }

        return $book;
    }

    private function bookService(): BookService
    {
        return new BookService((string) Yii::$app->params['bookImageStorageRoot']);
    }

    /**
     * @return Author[]
     */
    private function authorOptions(): array
    {
        return Author::find()->orderBy(['full_name' => SORT_ASC, 'id' => SORT_ASC])->all();
    }
}
