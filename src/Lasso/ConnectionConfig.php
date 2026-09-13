<?php

namespace Concrete\Package\LassoCrm\Lasso;

use Concrete\Core\Package\PackageService;
use Concrete\Core\Support\Facade\Application;

class ConnectionConfig
{
    public const KEY_API_KEY = 'api_key';
    public const KEY_TRACKING_ACCOUNT_ID = 'tracking_account_id';
    public const KEY_THANK_YOU_EMAIL_TEMPLATE_ID = 'thank_you_email_template_id';
    public const KEY_DEFAULT_SOURCE_TYPE = 'default_source_type';
    public const KEY_GLOBAL_TRACKING = 'global_tracking_enabled';
    public const KEY_LAST_SUCCESSFUL_PING = 'last_successful_ping';

    /** @var object|null Package config repository */
    private $config;

    public function __construct(?object $config = null)
    {
        if ($config !== null) {
            $this->config = $config;

            return;
        }

        $app = Application::getFacadeApplication();
        /** @var PackageService $packageService */
        $packageService = $app->make(PackageService::class);
        $pkg = $packageService->getByHandle('lasso_crm');
        $this->config = $pkg ? $pkg->getConfig() : null;
    }

    public function getApiKey(): string
    {
        return (string) $this->get(self::KEY_API_KEY, '');
    }

    public function getTrackingAccountId(): string
    {
        return (string) $this->get(self::KEY_TRACKING_ACCOUNT_ID, '');
    }

    public function getThankYouEmailTemplateId(): string
    {
        return (string) $this->get(self::KEY_THANK_YOU_EMAIL_TEMPLATE_ID, '');
    }

    public function getDefaultSourceType(): string
    {
        $value = (string) $this->get(self::KEY_DEFAULT_SOURCE_TYPE, 'Online Registration');

        return $value !== '' ? $value : 'Online Registration';
    }

    public function isGlobalTrackingEnabled(): bool
    {
        return (bool) $this->get(self::KEY_GLOBAL_TRACKING, false);
    }

    public function getLastSuccessfulPing(): string
    {
        return (string) $this->get(self::KEY_LAST_SUCCESSFUL_PING, '');
    }

    /**
     * Resolve the API key for a request: block override first, then package config.
     */
    public function resolveApiKey(?string $blockOverride = null): string
    {
        $override = trim((string) $blockOverride);
        if ($override !== '') {
            return $override;
        }

        return $this->getApiKey();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function save(array $values): void
    {
        if ($this->config === null) {
            return;
        }

        foreach ($values as $key => $value) {
            $this->config->save($key, $value);
        }
    }

    public function markSuccessfulPing(): void
    {
        $this->save([self::KEY_LAST_SUCCESSFUL_PING => date('c')]);
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    private function get(string $key, $default = null)
    {
        if ($this->config === null) {
            return $default;
        }

        return $this->config->get($key, $default);
    }
}
