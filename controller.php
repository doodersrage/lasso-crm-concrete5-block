<?php

namespace Concrete\Package\LassoCrm;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Page\Page;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Core\Support\Facade\Events;
use Concrete\Core\Validator\String\EmailValidator;
use Concrete\Core\View\View;
use Concrete\Package\LassoCrm\Lasso\ApiClient;
use Concrete\Package\LassoCrm\Lasso\ConnectionConfig;
use Concrete\Package\LassoCrm\Lasso\QuestionAnswerParser;
use Concrete\Package\LassoCrm\Lasso\RegistrantClient;
use Concrete\Package\LassoCrm\Lasso\RegistrantPayloadBuilder;
use Concrete\Package\LassoCrm\Lasso\SubmissionValidator;
use Concrete\Package\LassoCrm\Tracking\AnalyticsInjector;
use GuzzleHttp\Client as GuzzleClient;

class Controller extends Package
{
    protected $pkgHandle = 'lasso_crm';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '3.0.1';
    protected $phpVersionRequired = '8.0.0';

    protected $pkgAutoloaderRegistries = [
        'src' => 'Concrete\Package\LassoCrm',
    ];

    /** @var string[] */
    private $blockHandles = [
        'lasso_forms',
        'lasso_inventory',
        'lasso_appointments',
        'lasso_tracking',
    ];

    public function getPackageName()
    {
        return t('Lasso CRM');
    }

    public function getPackageDescription()
    {
        return t('Integrate Lasso CRM lead forms, inventory, appointments, and website tracking with Concrete CMS.');
    }

    public function on_start()
    {
        $app = $this->getApplication();

        $app->singleton(ConnectionConfig::class, static function () {
            return new ConnectionConfig();
        });

        $app->singleton(ApiClient::class, static function ($app) {
            return new ApiClient(new GuzzleClient(), $app->make(ConnectionConfig::class));
        });

        $app->singleton(RegistrantClient::class, static function ($app) {
            return new RegistrantClient($app->make(ApiClient::class));
        });

        $app->singleton(QuestionAnswerParser::class, static function () {
            return new QuestionAnswerParser();
        });

        $app->singleton(RegistrantPayloadBuilder::class, static function ($app) {
            return new RegistrantPayloadBuilder(
                $app->make(QuestionAnswerParser::class),
                $app->make(ConnectionConfig::class)
            );
        });

        $app->singleton(SubmissionValidator::class, static function ($app) {
            return new SubmissionValidator($app->make(EmailValidator::class));
        });

        $app->singleton(AnalyticsInjector::class, static function ($app) {
            return new AnalyticsInjector($app->make(ConnectionConfig::class));
        });

        Events::addListener('on_page_view', function ($event) use ($app) {
            $page = $event->getPageObject();
            if (!$page instanceof Page || $page->isError()) {
                return;
            }

            $dashboard = $app->make('helper/concrete/dashboard');
            if ($dashboard->inDashboard($page) || $page->isAdminArea()) {
                return;
            }

            /** @var AnalyticsInjector $injector */
            $injector = $app->make(AnalyticsInjector::class);
            if (!$injector->shouldInjectGlobally()) {
                return;
            }

            $script = $injector->getScriptHtml();
            if ($script === '') {
                return;
            }

            $v = View::getInstance();
            $v->addFooterItem($script);
        });
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installContent($pkg);

        return $pkg;
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->migrateLegacyBlockTable();
        $this->migrateBlockApiKeyToPackageConfig();
        $this->installContent($this);
    }

    private function installContent(Package $pkg): void
    {
        foreach ($this->blockHandles as $handle) {
            $bt = BlockType::getByHandle($handle);
            if (!is_object($bt)) {
                BlockType::installBlockType($handle, $pkg);
            }
        }

        $this->installSinglePages($pkg);
    }

    private function installSinglePages(Package $pkg): void
    {
        $pages = [
            '/dashboard/lasso_crm' => t('Lasso CRM'),
            '/dashboard/lasso_crm/settings' => t('Settings'),
        ];

        foreach ($pages as $path => $name) {
            $page = Page::getByPath($path);
            if (!is_object($page) || $page->isError()) {
                $page = SinglePage::add($path, $pkg);
            }
            if (is_object($page) && !$page->isError()) {
                $page->update(['cName' => $name]);
            }
        }
    }

    private function migrateLegacyBlockTable(): void
    {
        /** @var Connection $db */
        $db = $this->getApplication()->make(Connection::class);
        $schemaManager = $db->createSchemaManager();

        if ($schemaManager->tablesExist(['btLMSBlockContent']) && !$schemaManager->tablesExist(['btLassoForms'])) {
            $schemaManager->renameTable('btLMSBlockContent', 'btLassoForms');
        }
    }

    private function migrateBlockApiKeyToPackageConfig(): void
    {
        $config = new ConnectionConfig($this->getConfig());
        if ($config->getApiKey() !== '') {
            return;
        }

        /** @var Connection $db */
        $db = $this->getApplication()->make(Connection::class);
        $schemaManager = $db->createSchemaManager();
        if (!$schemaManager->tablesExist(['btLassoForms'])) {
            return;
        }

        $columns = $schemaManager->listTableColumns('btLassoForms');
        if (!isset($columns['apikey']) && !isset($columns['apiKey'])) {
            return;
        }

        try {
            $key = $db->fetchOne('SELECT apiKey FROM btLassoForms WHERE apiKey IS NOT NULL AND apiKey != \'\' ORDER BY bID ASC LIMIT 1');
        } catch (\Throwable $e) {
            return;
        }

        if (is_string($key) && trim($key) !== '') {
            $config->save([ConnectionConfig::KEY_API_KEY => trim($key)]);
        }
    }
}
