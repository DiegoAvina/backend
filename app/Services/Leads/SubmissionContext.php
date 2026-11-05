<?php

namespace App\Services\Leads;

/**
 * Metadatos técnicos de un envío. La IP nunca se guarda en claro: solo su
 * HMAC, suficiente para detectar abuso sin conservar el dato personal.
 */
final readonly class SubmissionContext
{
    /**
     * @param  array<string, string>|null  $utm
     */
    public function __construct(
        public ?string $ipHash,
        public ?string $userAgent,
        public ?string $idempotencyKey,
        public ?array $utm,
    ) {}

    public static function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
