<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\integrations\smspilot\SmsPilotSendResponse;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yii;

final class SmsPilotSendResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::setAlias('@app', dirname(__DIR__, 2));
    }

    #[TestDox('Документированный ответ преобразуется в типизированное подтверждение отправки')]
    public function testDocumentedSuccessShapeIsMapped(): void
    {
        $response = SmsPilotSendResponse::fromArray([
            'send' => [[
                'server_id' => '9316849',
                'phone' => '79087964781',
                'price' => '1.31',
                'status' => '0',
            ]],
            'balance' => '2935.50',
            'cost' => '1.31',
        ]);

        self::assertSame('9316849', $response->serverId);
        self::assertSame('79087964781', $response->phone);
        self::assertSame(0, $response->status);
    }

    #[TestDox('Отсутствующая запись send отклоняется')]
    public function testMissingSendShapeIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SMSPilot вернул некорректное подтверждение отправки.');

        SmsPilotSendResponse::fromArray([]);
    }

    #[TestDox('Пустой идентификатор сервера отклоняется')]
    public function testBlankServerIdIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SMSPilot вернул некорректное подтверждение отправки.');

        SmsPilotSendResponse::fromArray([
            'send' => [[
                'server_id' => ' ',
                'phone' => '79087964781',
                'status' => '0',
            ]],
        ]);
    }

    #[TestDox('Не целочисленный статус отклоняется')]
    public function testNonIntegerStatusIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SMSPilot вернул некорректное подтверждение отправки.');

        SmsPilotSendResponse::fromArray([
            'send' => [[
                'server_id' => '9316849',
                'phone' => '79087964781',
                'status' => 'pending',
            ]],
        ]);
    }
}
