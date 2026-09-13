<?php

namespace Concrete\Package\LassoCrm\Block\LassoTracking;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Package\LassoCrm\Lasso\ConnectionConfig;
use Concrete\Package\LassoCrm\Tracking\AnalyticsInjector;

class Controller extends BlockController
{
    /** @var string|null */
    public $accountId;

    protected $btTable = 'btLassoTracking';
    protected $btInterfaceWidth = 500;
    protected $btInterfaceHeight = 320;
    protected $btDefaultSet = 'multimedia';
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputForRegisteredUsers = true;

    public function getBlockTypeName()
    {
        return t('Lasso Website Tracking');
    }

    public function getBlockTypeDescription()
    {
        return t('Inject Lasso Analytics website tracking for the current page.');
    }

    public function add()
    {
        $this->formSetup();
    }

    public function edit()
    {
        $this->formSetup();
    }

    protected function formSetup(): void
    {
        $this->set('form', $this->app->make('helper/form'));
        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        $this->set('packageTrackingAccountId', $config->getTrackingAccountId());
        $this->set('globalTrackingEnabled', $config->isGlobalTrackingEnabled());
    }

    public function view()
    {
        /** @var AnalyticsInjector $injector */
        $injector = $this->app->make(AnalyticsInjector::class);
        $this->set('trackingScript', $injector->getScriptHtml($this->accountId));
    }

    public function save($args)
    {
        $args['accountId'] = isset($args['accountId']) ? trim((string) $args['accountId']) : '';
        parent::save($args);
    }
}
