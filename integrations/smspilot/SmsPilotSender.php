<?php

declare(strict_types=1);

namespace app\integrations\smspilot;

use app\integrations\SmsSenderInterface;
use RuntimeException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\httpclient\Exception as HttpClientException;

final class SmsPilotSender implements SmsSenderInterface
{
    private const string ENDPOINT = 'https://smspilot.ru/api.php';
    private const int TIMEOUT_SECONDS = 3;

    private readonly Client $client;

    public function __construct(
        private readonly string $apiKey,
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client();
        $this->client->contentLoggingMaxSize = 0;
    }

    public function send(string $phone, string $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new RuntimeException('Ключ SMSPilot не настроен.');
        }

        try {
            $response = $this->client->createRequest()
                ->setMethod('POST')
                ->setUrl(self::ENDPOINT)
                ->setFormat(Client::FORMAT_RAW_URLENCODED)
                ->setData([
                    'send' => $message,
                    'to' => $phone,
                    'apikey' => $this->apiKey,
                    'format' => 'json',
                    'test' => '1',
                ])
                ->setOptions([
                    'timeout' => self::TIMEOUT_SECONDS,
                    'sslVerifyPeer' => true,
                ])
                ->send();
            $isOk = $response->getIsOk();
        } catch (InvalidConfigException $exception) {
            throw new RuntimeException('Не удалось подготовить HTTP-запрос к SMSPilot.', 0, $exception);
        } catch (HttpClientException $exception) {
            throw new RuntimeException('Не удалось связаться с SMSPilot.', 0, $exception);
        }

        if (!$isOk) {
            throw new RuntimeException('SMSPilot вернул ошибку HTTP.');
        }

        try {
            $data = Json::decode($response->getContent(), true);
        } catch (InvalidArgumentException $exception) {
            throw new RuntimeException(
                'SMSPilot вернул некорректный ответ.',
                0,
                $exception,
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException('SMSPilot вернул некорректный ответ.');
        }

        if (array_key_exists('error', $data)) {
            throw new RuntimeException('SMSPilot отклонил запрос.');
        }

        /** @var array<string, mixed> $data */
        SmsPilotSendResponse::fromArray($data);
    }
}
