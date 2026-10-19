<?php

namespace App\Services;

/** Outcome of sending one SMS through SmsGateway. */
class SmsSendResult
{
    public function __construct(
        public bool $ok,
        public ?string $messageId,
        public string $description,
        public array $raw = [],
        public bool $ownGateway = false,
        public int $statusCode = 200,
    ) {}

    public static function sent(?string $messageId, string $description, array $raw, bool $ownGateway, int $statusCode = 200): self
    {
        return new self(true, $messageId, $description, $raw, $ownGateway, $statusCode);
    }

    public static function failed(string $description, bool $ownGateway, int $statusCode = 400, array $raw = []): self
    {
        return new self(false, null, $description, $raw, $ownGateway, $statusCode);
    }

    // Same shape as the HTTP response the SMS controller used before.
    public function successful(): bool { return $this->ok; }
    public function status(): int { return $this->statusCode; }
}
