<?php

namespace Concrete\Package\LassoCrm\Lasso;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class ApiClient
{
    private const BASE_URL = 'https://api.lassocrm.com/v1';

    /** @var ClientInterface */
    private $httpClient;

    /** @var ConnectionConfig */
    private $connectionConfig;

    public function __construct(ClientInterface $httpClient, ConnectionConfig $connectionConfig)
    {
        $this->httpClient = $httpClient;
        $this->connectionConfig = $connectionConfig;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{success: bool, message?: string, data?: array<string, mixed>, status?: int}
     */
    public function createRegistrant(array $payload, ?string $apiKey = null): array
    {
        return $this->request('POST', '/registrants', $this->resolveKey($apiKey), [
            'json' => $payload,
            'successCodes' => [201],
        ]);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function listRegistrants(array $query = [], ?string $apiKey = null): array
    {
        return $this->request('GET', '/registrants', $this->resolveKey($apiKey), [
            'query' => $query,
        ]);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function searchRegistrants(array $query = [], ?string $apiKey = null): array
    {
        return $this->request('GET', '/registrants/search', $this->resolveKey($apiKey), [
            'query' => $query,
        ]);
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function getProjectSettings(?string $apiKey = null): array
    {
        $result = $this->request('GET', '/projects/settings', $this->resolveKey($apiKey));
        if ($result['success']) {
            $this->connectionConfig->markSuccessfulPing();
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function listInventory(array $query = [], ?string $apiKey = null): array
    {
        return $this->request('GET', '/inventory', $this->resolveKey($apiKey), [
            'query' => $query,
        ]);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function searchInventory(array $query = [], ?string $apiKey = null): array
    {
        return $this->request('GET', '/inventory/search', $this->resolveKey($apiKey), [
            'query' => $query,
        ]);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function listProjectAppointments(array $query = [], ?string $apiKey = null): array
    {
        return $this->request('GET', '/projects/appointments', $this->resolveKey($apiKey), [
            'query' => $query,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function createRegistrantAppointment(string $registrantId, array $payload, ?string $apiKey = null): array
    {
        return $this->request('POST', '/registrants/' . rawurlencode($registrantId) . '/appointments', $this->resolveKey($apiKey), [
            'json' => $payload,
            'successCodes' => [200, 201],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function addRegistrantNote(string $registrantId, array $payload, ?string $apiKey = null): array
    {
        return $this->request('POST', '/registrants/' . rawurlencode($registrantId) . '/notes', $this->resolveKey($apiKey), [
            'json' => $payload,
            'successCodes' => [200, 201],
        ]);
    }

    /**
     * @param array{json?: array<string, mixed>, query?: array<string, mixed>, successCodes?: int[]} $options
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    private function request(string $method, string $path, string $apiKey, array $options = []): array
    {
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => t('A Lasso API key is not configured. Set one under Dashboard → Lasso CRM → Settings.'),
            ];
        }

        $successCodes = $options['successCodes'] ?? [200];
        $requestOptions = [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'http_errors' => false,
        ];

        if (isset($options['json'])) {
            $requestOptions['json'] = $options['json'];
        }
        if (isset($options['query'])) {
            $requestOptions['query'] = array_filter(
                $options['query'],
                static function ($value) {
                    return $value !== null && $value !== '';
                }
            );
        }

        try {
            $response = $this->httpClient->request($method, self::BASE_URL . $path, $requestOptions);
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'message' => t('Unable to connect to Lasso CRM.') . ' ' . $e->getMessage(),
            ];
        }

        return $this->shapeResponse($response, $successCodes);
    }

    /**
     * @param int[] $successCodes
     *
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    private function shapeResponse(ResponseInterface $response, array $successCodes): array
    {
        $httpCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        $data = is_array($decoded) ? $decoded : null;

        if (in_array($httpCode, $successCodes, true)) {
            return [
                'success' => true,
                'data' => $data,
                'status' => $httpCode,
            ];
        }

        $message = t('Lasso CRM rejected the request.');
        if (is_array($data)) {
            if (!empty($data['message'])) {
                $message = (string) $data['message'];
            } elseif (!empty($data['error'])) {
                $message = is_string($data['error']) ? $data['error'] : json_encode($data['error']);
            }
        }

        return [
            'success' => false,
            'message' => $message . ' (' . t('HTTP %s', $httpCode) . ')',
            'data' => $data,
            'status' => $httpCode,
        ];
    }

    private function resolveKey(?string $apiKey): string
    {
        return $this->connectionConfig->resolveApiKey($apiKey);
    }
}
