<?php

declare(strict_types=1);

namespace tests\integration;

use app\models\Author;
use app\models\Subscription;
use app\models\SubscriptionForm;
use PHPUnit\Framework\Attributes\TestDox;
use tests\TestCase;

final class SubscriptionTest extends TestCase
{
    #[TestDox('Эквивалентные форматы телефона сохраняются в одном каноническом виде и не дублируются у автора')]
    public function testEquivalentPhonesAreNormalizedAndRejectedAsDuplicatesForOneAuthor(): void
    {
        $author = $this->createAuthor('Первый автор');
        $firstForm = new SubscriptionForm($author->id, ['phone' => '+1 (234) 567-89-01']);

        self::assertTrue($firstForm->validate());
        self::assertSame('12345678901', $firstForm->phone);
        self::assertTrue((new Subscription([
            'author_id' => $author->id,
            'phone' => $firstForm->phone,
        ]))->save());

        $duplicateForm = new SubscriptionForm($author->id, ['phone' => '1 234 567 8901']);

        self::assertFalse($duplicateForm->validate());
        self::assertNotEmpty($duplicateForm->getErrors('phone'));
    }

    #[TestDox('Телефон с недопустимыми символами отклоняется')]
    public function testInvalidPhoneIsRejected(): void
    {
        $form = new SubscriptionForm($this->createAuthor('Автор')->id, ['phone' => '+1.234.567.8901']);

        self::assertFalse($form->validate());
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
        self::assertTrue((new Subscription([
            'author_id' => $firstAuthor->id,
            'phone' => $firstForm->phone,
        ]))->save());
        self::assertTrue($secondForm->validate());
        self::assertTrue((new Subscription([
            'author_id' => $secondAuthor->id,
            'phone' => $secondForm->phone,
        ]))->save());
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

    private function createAuthor(string $fullName): Author
    {
        $author = new Author(['full_name' => $fullName]);
        self::assertTrue($author->save());

        return $author;
    }
}
