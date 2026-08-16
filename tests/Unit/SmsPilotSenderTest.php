<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\integrations\smspilot\SmsPilotSender;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yii;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;
use yii\httpclient\MockTransport;
use yii\httpclient\Response;

final class SmsPilotSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::setAlias('@app', dirname(__DIR__, 2));
    }

    #[TestDox('Успешный ответ подтверждает точный запрос к HTTPS-эмулятору с обязательным test=1')]
    public function testSuccessfulResponseUsesExactEmulatorRequest(): void
    {
        [$client, $transport] = $this->clientWithResponse(
            200,
            '{"send":[{"server_id":"123456","phone":"79991234567","status":"0"}]}',
        );
        $sender = new SmsPilotSender('unit-test-api-key', $client);

        $sender->send('79991234567', 'Тестовое сообщение.');

        $requests = $transport->flushRequests();
        self::assertCount(1, $requests);
        $request = $requests[0];
        self::assertSame('https://smspilot.ru/api.php', $request->getUrl());
        self::assertSame('POST', $request->getMethod());
        self::assertSame(Client::FORMAT_RAW_URLENCODED, $request->getFormat());
        self::assertSame([
            'send' => 'Тестовое сообщение.',
            'to' => '79991234567',
            'apikey' => 'unit-test-api-key',
            'format' => 'json',
            'test' => '1',
        ], $request->getData());
        self::assertSame(['timeout' => 3, 'sslVerifyPeer' => true], $request->getOptions());
        self::assertSame(0, $client->contentLoggingMaxSize);
    }

    #[TestDox('Пустой API-ключ отклоняется до вызова транспорта')]
    public function testEmptyApiKeyFailsBeforeTransport(): void
    {
        $transport = new MockTransport();
        $sender = new SmsPilotSender('   ', new Client(['transport' => $transport]));

        try {
            $sender->send('79991234567', 'Тестовое сообщение.');
            self::fail('Expected an empty API key failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('Ключ SMSPilot не настроен.', $exception->getMessage());
            self::assertSame([], $transport->flushRequests());
        }
    }

    #[TestDox('Ошибка провайдера не раскрывает ключ, описания и сырой ответ')]
    public function testProviderErrorIsSanitized(): void
    {
        $apiKey = 'secret-unit-test-api-key';
        $rawResponse = '{"error":{"code":"111","description":"private provider detail"}}';
        [$client] = $this->clientWithResponse(200, $rawResponse);
        $sender = new SmsPilotSender($apiKey, $client);

        try {
            $sender->send('79991234567', 'Тестовое сообщение.');
            self::fail('Expected a provider error response failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('SMSPilot отклонил запрос.', $exception->getMessage());
            self::assertStringNotContainsString($apiKey, $exception->getMessage());
            self::assertStringNotContainsString($rawResponse, $exception->getMessage());
            self::assertStringNotContainsString('private provider detail', $exception->getMessage());
        }
    }

    #[TestDox('Повреждённый JSON провайдера отклоняется безопасной ошибкой')]
    public function testMalformedJsonIsRejected(): void
    {
        [$client] = $this->clientWithResponse(200, '{invalid-json');
        $sender = new SmsPilotSender('unit-test-api-key', $client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SMSPilot вернул некорректный ответ.');

        $sender->send('79991234567', 'Тестовое сообщение.');
    }

    #[TestDox('JSON с неверным типом корневого значения отклоняется')]
    public function testNonObjectJsonIsRejected(): void
    {
        [$client] = $this->clientWithResponse(200, '"accepted"');
        $sender = new SmsPilotSender('unit-test-api-key', $client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SMSPilot вернул некорректный ответ.');

        $sender->send('79991234567', 'Тестовое сообщение.');
    }

    #[TestDox('Неуспешный HTTP-ответ отклоняется до разбора тела')]
    public function testNonSuccessfulHttpResponseIsRejected(): void
    {
        [$client] = $this->clientWithResponse(503, 'private upstream response');
        $sender = new SmsPilotSender('unit-test-api-key', $client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SMSPilot вернул ошибку HTTP.');

        $sender->send('79991234567', 'Тестовое сообщение.');
    }

    #[TestDox('Ошибка транспорта преобразуется в безопасное исключение')]
    public function testTransportFailureIsSanitized(): void
    {
        $transport = new MockTransport();
        $sender = new SmsPilotSender(
            'unit-test-api-key',
            new Client(['transport' => $transport]),
        );

        try {
            $sender->send('79991234567', 'Тестовое сообщение.');
            self::fail('Expected a transport failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('Не удалось связаться с SMSPilot.', $exception->getMessage());
            self::assertSame([], $transport->flushRequests());
            self::assertStringNotContainsString('unit-test-api-key', $exception->getMessage());
        }
    }

    #[TestDox('Некорректная конфигурация клиента преобразуется в ошибку адаптера')]
    public function testInvalidRequestConfigurationIsSanitized(): void
    {
        $apiKey = 'secret-unit-test-api-key';
        $sender = new SmsPilotSender($apiKey, new Client([
            'requestConfig' => ['class' => 'Tests\\Unit\\MissingSmsPilotRequest'],
        ]));

        try {
            $sender->send('79991234567', 'Тестовое сообщение.');
            self::fail('Expected an invalid request configuration failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('Не удалось подготовить HTTP-запрос к SMSPilot.', $exception->getMessage());
            self::assertInstanceOf(InvalidConfigException::class, $exception->getPrevious());
            self::assertStringNotContainsString($apiKey, $exception->getMessage());
        }
    }

    /**
     * @return array{Client, MockTransport}
     */
    private function clientWithResponse(int $statusCode, string $content): array
    {
        $transport = new MockTransport();
        $response = new Response();
        $response->setHeaders([
            sprintf('HTTP/1.1 %d Test Response', $statusCode),
            'Content-Type: application/json',
        ]);
        $response->setContent($content);
        $transport->appendResponse($response);

        return [
            new Client(['transport' => $transport]),
            $transport,
        ];
    }
}
