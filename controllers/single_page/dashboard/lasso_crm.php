<?php

namespace Concrete\Package\LassoCrm\Controller\SinglePage\Dashboard;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Package\LassoCrm\Lasso\ApiClient;
use Concrete\Package\LassoCrm\Lasso\ConnectionConfig;

class LassoCrm extends DashboardPageController
{
    public function view()
    {
        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        /** @var ApiClient $client */
        $client = $this->app->make(ApiClient::class);

        $this->set('hasApiKey', $config->getApiKey() !== '');
        $this->set('trackingAccountId', $config->getTrackingAccountId());
        $this->set('globalTrackingEnabled', $config->isGlobalTrackingEnabled());
        $this->set('lastSuccessfulPing', $config->getLastSuccessfulPing());
        $this->set('defaultSourceType', $config->getDefaultSourceType());

        $connectionOk = false;
        $connectionMessage = '';
        $projectSettings = null;
        $registrants = [];

        if ($config->getApiKey() !== '') {
            $settingsResult = $client->getProjectSettings();
            $connectionOk = $settingsResult['success'];
            $connectionMessage = $settingsResult['success']
                ? t('Connected to Lasso CRM.')
                : ($settingsResult['message'] ?? t('Unable to connect.'));
            $projectSettings = $settingsResult['data'] ?? null;

            $listResult = $client->listRegistrants();
            if ($listResult['success'] && is_array($listResult['data'])) {
                $registrants = $this->extractRegistrants($listResult['data']);
            }
        } else {
            $connectionMessage = t('Configure an API key under Settings to connect.');
        }

        $this->set('connectionOk', $connectionOk);
        $this->set('connectionMessage', $connectionMessage);
        $this->set('projectSettings', $projectSettings);
        $this->set('registrants', $registrants);
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    private function extractRegistrants(array $data): array
    {
        $items = [];
        if (isset($data['registrants']) && is_array($data['registrants'])) {
            $items = $data['registrants'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        } elseif ($data !== [] && array_keys($data) === range(0, count($data) - 1)) {
            $items = $data;
        }

        return array_slice($items, 0, 20);
    }
}
