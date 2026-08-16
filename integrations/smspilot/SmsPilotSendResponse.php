<?php

declare(strict_types=1);

namespace app\integrations\smspilot;

use RuntimeException;

final readonly class SmsPilotSendResponse
{
    public function __construct(
        public string $serverId,
        public string $phone,
        public int $status,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $send = $data['send'] ?? null;
        if (!is_array($send) || !isset($send[0]) || !is_array($send[0])) {
            throw new RuntimeException('SMSPilot вернул некорректное подтверждение отправки.');
        }

        $serverId = $send[0]['server_id'] ?? null;
        $phone = $send[0]['phone'] ?? null;
        $statusValue = $send[0]['status'] ?? null;

        if (
            !is_scalar($serverId)
            || trim((string) $serverId) === ''
            || !is_scalar($phone)
            || trim((string) $phone) === ''
            || (!is_int($statusValue) && !is_string($statusValue))
        ) {
            throw new RuntimeException('SMSPilot вернул некорректное подтверждение отправки.');
        }

        $status = filter_var($statusValue, FILTER_VALIDATE_INT);
        if ($status === false) {
            throw new RuntimeException('SMSPilot вернул некорректное подтверждение отправки.');
        }

        return new self((string) $serverId, (string) $phone, $status);
    }
}
