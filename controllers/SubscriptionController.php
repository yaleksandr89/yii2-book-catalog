<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Author;
use app\models\Subscription;
use app\models\SubscriptionForm;
use Yii;
use yii\db\Exception as YiiDbException;
use yii\db\IntegrityException;
use yii\web\Application;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class SubscriptionController extends Controller
{
    /**
     * @throws NotFoundHttpException
     * @throws YiiDbException
     */
    public function actionCreate(int $authorId): Response|string
    {
        $author = Author::findOne($authorId);
        if ($author === null) {
            throw new NotFoundHttpException('Автор не найден.');
        }

        $form = new SubscriptionForm($author->id);
        $post = $this->request->post();
        if (is_array($post) && $form->load($post) && $form->validate()) {
            $subscription = new Subscription([
                'author_id' => $author->id,
                'phone' => $form->getNormalizedPhone(),
            ]);

            try {
                $subscription->save(false);
            } catch (IntegrityException) {
                $form->addError('phone', 'Этот номер уже подписан на автора.');

                return $this->render('create', ['author' => $author, 'form' => $form]);
            }

            $app = Yii::$app;
            assert($app instanceof Application);
            $app->session->setFlash('success', 'Вы подписались на автора.');

            return $this->redirect(['/author/view', 'id' => $author->id]);
        }

        return $this->render('create', ['author' => $author, 'form' => $form]);
    }
}
