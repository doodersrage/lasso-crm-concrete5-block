<?php

namespace Concrete\Package\LassoCrm\Lasso;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class RegistrantClient
{
    private const API_URL = 'https://api.lassocrm.com/v1/registrants';

    /** @var ClientInterface */
    private $httpClient;

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{success: bool, message?: string}
     */
    public function createRegistrant(string $apiKey, array $payload): array
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'json' => $payload,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
            ]);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => t('Unable to connect to Lasso CRM.') . ' ' . $e->getMessage(),
            ];
        }

        $httpCode = $response->getStatusCode();
        if ($httpCode === 201) {
            return ['success' => true];
        }

        $responseBody = (string) $response->getBody();
        $decoded = json_decode($responseBody, true);
        $message = t('Lasso CRM rejected the submission.');

        if (is_array($decoded)) {
            if (!empty($decoded['message'])) {
                $message = (string) $decoded['message'];
            } elseif (!empty($decoded['error'])) {
                $message = is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']);
            }
        }

        return [
            'success' => false,
            'message' => $message . ' (' . t('HTTP %s', $httpCode) . ')',
        ];
    }
}
