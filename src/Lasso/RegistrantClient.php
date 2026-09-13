<?php

namespace Concrete\Package\LassoCrm\Lasso;

/**
 * Backwards-compatible wrapper around ApiClient for registrant creation.
 *
 * @deprecated Use ApiClient::createRegistrant() instead.
 */
class RegistrantClient
{
    /** @var ApiClient */
    private $apiClient;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function createRegistrant(string $apiKey, array $payload): array
    {
        return $this->apiClient->createRegistrant($payload, $apiKey);
    }
}
