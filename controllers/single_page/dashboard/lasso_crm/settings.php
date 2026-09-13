<?php

namespace Concrete\Package\LassoCrm\Controller\SinglePage\Dashboard\LassoCrm;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Package\LassoCrm\Lasso\ApiClient;
use Concrete\Package\LassoCrm\Lasso\ConnectionConfig;

class Settings extends DashboardPageController
{
    public function view()
    {
        $this->loadSettings();
    }

    public function save()
    {
        if (!$this->token->validate('lasso_crm_settings')) {
            $this->error->add($this->token->getErrorMessage());
            $this->loadSettings();

            return;
        }

        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);

        $apiKey = trim((string) $this->request->request->get('api_key'));
        $existingKey = $config->getApiKey();
        if ($apiKey === '' && $existingKey !== '') {
            $apiKey = $existingKey;
        }

        $config->save([
            ConnectionConfig::KEY_API_KEY => $apiKey,
            ConnectionConfig::KEY_TRACKING_ACCOUNT_ID => trim((string) $this->request->request->get('tracking_account_id')),
            ConnectionConfig::KEY_THANK_YOU_EMAIL_TEMPLATE_ID => trim((string) $this->request->request->get('thank_you_email_template_id')),
            ConnectionConfig::KEY_DEFAULT_SOURCE_TYPE => trim((string) $this->request->request->get('default_source_type')) ?: 'Online Registration',
            ConnectionConfig::KEY_GLOBAL_TRACKING => (bool) $this->request->request->get('global_tracking_enabled'),
        ]);

        $this->flash('success', t('Lasso CRM settings saved.'));
        $this->redirect('/dashboard/lasso_crm/settings');
    }

    public function test_connection()
    {
        if (!$this->token->validate('lasso_crm_test_connection')) {
            $this->error->add($this->token->getErrorMessage());
            $this->loadSettings();

            return;
        }

        /** @var ApiClient $client */
        $client = $this->app->make(ApiClient::class);
        $result = $client->getProjectSettings();

        if ($result['success']) {
            $this->flash('success', t('Connection successful. Project settings retrieved from Lasso CRM.'));
        } else {
            $this->error->add($result['message'] ?? t('Connection failed.'));
        }

        $this->redirect('/dashboard/lasso_crm/settings');
    }

    private function loadSettings(): void
    {
        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);

        $this->set('apiKeyConfigured', $config->getApiKey() !== '');
        $this->set('trackingAccountId', $config->getTrackingAccountId());
        $this->set('thankYouEmailTemplateId', $config->getThankYouEmailTemplateId());
        $this->set('defaultSourceType', $config->getDefaultSourceType());
        $this->set('globalTrackingEnabled', $config->isGlobalTrackingEnabled());
        $this->set('lastSuccessfulPing', $config->getLastSuccessfulPing());
    }
}
