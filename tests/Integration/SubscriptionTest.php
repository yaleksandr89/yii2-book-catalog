<?php

declare(strict_types=1);

namespace Tests\Integration;

use app\models\Author;
use app\models\Subscription;
use app\models\SubscriptionForm;
use LogicException;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Yii;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

final class SubscriptionTest extends TestCase
{
    #[TestDox('Нормализованный телефон недоступен до успешной валидации')]
    public function testNormalizedPhoneRequiresSuccessfulValidation(): void
    {
        $form = new SubscriptionForm(
            $this->createAuthor('Автор непроверенной подписки')->id,
            ['phone' => '+1 (234) 567-89-01'],
        );

        $this->expectException(LogicException::class);
        $form->getNormalizedPhone();
    }

    #[TestDox('Эквивалентные форматы телефона сохраняются в одном каноническом виде и не дублируются у автора')]
    public function testEquivalentPhonesAreNormalizedAndRejectedAsDuplicatesForOneAuthor(): void
    {
        $author = $this->createAuthor('Первый автор');
        $firstForm = new SubscriptionForm($author->id, ['phone' => '+1 (234) 567-89-01']);

        self::assertTrue($firstForm->validate());
        self::assertSame('+1 (234) 567-89-01', $firstForm->phone);
        self::assertSame('12345678901', $firstForm->getNormalizedPhone());

        self::assertTrue(new Subscription([
            'author_id' => $author->id,
            'phone' => $firstForm->getNormalizedPhone(),
        ])->save());

        $duplicateForm = new SubscriptionForm($author->id, ['phone' => '1 234 567 8901']);

        self::assertFalse($duplicateForm->validate());
        self::assertNotEmpty($duplicateForm->getErrors('phone'));
    }

    #[TestDox('Недопустимый телефон отклоняется без изменения пользовательского ввода')]
    public function testInvalidPhoneIsRejectedWithoutChangingOriginalInput(): void
    {
        $phone = '+1.234.567.8901';

        $form = new SubscriptionForm(
            $this->createAuthor('Автор')->id,
            ['phone' => $phone],
        );

        self::assertFalse($form->validate());
        self::assertSame($phone, $form->phone);
        self::assertNotEmpty($form->getErrors('phone'));
    }

    #[TestDox('Один канонический телефон разрешён для разных авторов')]
    public function testSamePhoneIsAllowedForDifferentAuthors(): void
    {
        $firstAuthor = $this->createAuthor('Первый автор');
        $secondAuthor = $this->createAuthor('Второй автор');
        $firstForm = new SubscriptionForm($firstAuthor->id, ['phone' => '+12345678901']);
        $secondForm = new SubscriptionForm($secondAuthor->id, ['phone' => '1 234 567 8901']);

        self::assertTrue($firstForm->validate());
        self::assertTrue(new Subscription([
            'author_id' => $firstAuthor->id,
            'phone' => $firstForm->getNormalizedPhone(),
        ])->save());

        self::assertTrue($secondForm->validate());

        self::assertTrue(new Subscription([
            'author_id' => $secondAuthor->id,
            'phone' => $secondForm->getNormalizedPhone(),
        ])->save());
    }

    #[TestDox('Гость открывает форму и подписывается на конкретного автора')]
    public function testGuestCanSubscribeToConcreteAuthor(): void
    {
        $author = $this->createAuthor('Автор для подписки');

        self::assertIsString($this->app->runAction('subscription/create', ['authorId' => $author->id]));

        $_POST = ['SubscriptionForm' => ['phone' => '+1 (234) 567-89-01']];
        $this->setRequestMethod('POST');
        $this->app->runAction('subscription/create', ['authorId' => $author->id]);

        self::assertSame(302, $this->app->response->statusCode);
        $subscription = Subscription::find()->where(['author_id' => $author->id])->one();
        self::assertNotNull($subscription);
        self::assertSame($author->id, $subscription->author_id);
        self::assertSame('12345678901', $subscription->phone);
    }

    #[TestDox('Уникальное ограничение защищает подписку от конкурентной вставки')]
    public function testConcurrentSubscriptionInsertReturnsDuplicateError(): void
    {
        $author = $this->createAuthor('Автор конкурентной подписки');
        $canonicalPhone = '12345678901';
        $handler = static function (Event $event) use ($author, $canonicalPhone): void {
            Yii::$app->db->createCommand()->insert('{{%subscription}}', [
                'author_id' => $author->id,
                'phone' => $canonicalPhone,
            ])->execute();
        };
        Event::on(Subscription::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        $_POST = ['SubscriptionForm' => ['phone' => '+1 (234) 567-89-01']];
        $this->setRequestMethod('POST');

        try {
            $html = $this->app->runAction('subscription/create', ['authorId' => $author->id]);
        } finally {
            Event::off(Subscription::class, ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        }

        self::assertIsString($html);
        self::assertStringContainsString('Этот номер уже подписан на автора.', $html);
        self::assertSame(
            1,
            (int) Subscription::find()
                ->where(['author_id' => $author->id, 'phone' => $canonicalPhone])
                ->count(),
        );
    }

    #[TestDox('Подписка на отсутствующего автора возвращает 404')]
    public function testSubscriptionForMissingAuthorReturnsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->app->runAction('subscription/create', ['authorId' => 999999]);
    }

    private function createAuthor(string $fullName): Author
    {
        $author = new Author(['full_name' => $fullName]);
        self::assertTrue($author->save());

        return $author;
    }
}
