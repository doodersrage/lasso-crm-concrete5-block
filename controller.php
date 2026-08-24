<?php

namespace Concrete\Package\LassoCrm;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Package\Package;
use Concrete\Core\Validator\String\EmailValidator;
use Concrete\Package\LassoCrm\Lasso\QuestionAnswerParser;
use Concrete\Package\LassoCrm\Lasso\RegistrantClient;
use Concrete\Package\LassoCrm\Lasso\RegistrantPayloadBuilder;
use Concrete\Package\LassoCrm\Lasso\SubmissionValidator;
use GuzzleHttp\Client as GuzzleClient;

class Controller extends Package
{
    protected $pkgHandle = 'lasso_crm';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '2.1.0';

    protected $pkgAutoloaderRegistries = [
        'src' => 'Concrete\Package\LassoCrm',
    ];

    public function getPackageName()
    {
        return t('Lasso CRM');
    }

    public function getPackageDescription()
    {
        return t('Adds a registrant lead capture form block integrated with Lasso CRM.');
    }

    public function on_start()
    {
        $app = $this->getApplication();

        $app->singleton(RegistrantClient::class, static function () {
            return new RegistrantClient(new GuzzleClient());
        });

        $app->singleton(QuestionAnswerParser::class, static function () {
            return new QuestionAnswerParser();
        });

        $app->singleton(RegistrantPayloadBuilder::class, static function ($app) {
            return new RegistrantPayloadBuilder($app->make(QuestionAnswerParser::class));
        });

        $app->singleton(SubmissionValidator::class, static function ($app) {
            return new SubmissionValidator($app->make(EmailValidator::class));
        });
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installBlockType($pkg);

        return $pkg;
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->migrateLegacyBlockTable();

        $pkg = Package::getByHandle($this->pkgHandle);
        if ($pkg) {
            $this->installBlockType($pkg);
        }
    }

    public function uninstall()
    {
        $bt = BlockType::getByHandle('lasso_forms');
        if (is_object($bt)) {
            $bt->delete();
        }

        parent::uninstall();
    }

    private function installBlockType(Package $pkg): void
    {
        $bt = BlockType::getByHandle('lasso_forms');
        if (!is_object($bt)) {
            BlockType::installBlockType('lasso_forms', $pkg);
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
}
