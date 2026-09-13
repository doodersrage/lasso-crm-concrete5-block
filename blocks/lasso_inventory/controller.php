<?php

namespace Concrete\Package\LassoCrm\Block\LassoInventory;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Package\LassoCrm\Lasso\ApiClient;
use Concrete\Package\LassoCrm\Lasso\ConnectionConfig;

class Controller extends BlockController
{
    /** @var string|null */
    public $apiKey;

    /** @var string|null */
    public $statusFilter;

    /** @var int|string|null */
    public $maxItems;

    /** @var int|string|null */
    public $showPrice;

    /** @var int|string|null */
    public $showPlan;

    /** @var int|string|null */
    public $showAvailability;

    /** @var string|null */
    public $emptyMessage;

    protected $btTable = 'btLassoInventory';
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 500;
    protected $btDefaultSet = 'multimedia';
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputForRegisteredUsers = true;
    protected $btCacheBlockOutputLifetime = 300;

    public function getBlockTypeName()
    {
        return t('Lasso Inventory');
    }

    public function getBlockTypeDescription()
    {
        return t('Display inventory availability from Lasso CRM.');
    }

    public function add()
    {
        $this->formSetup();
        $this->set('maxItems', 20);
        $this->set('showPrice', 1);
        $this->set('showPlan', 1);
        $this->set('showAvailability', 1);
        $this->set('emptyMessage', t('No inventory is available right now.'));
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
        $this->set('packageApiKeyConfigured', $config->getApiKey() !== '');
    }

    public function view()
    {
        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        $apiKey = $config->resolveApiKey($this->apiKey);

        $items = [];
        $error = null;

        if ($apiKey === '') {
            $error = t('Lasso API key is not configured.');
        } else {
            /** @var ApiClient $client */
            $client = $this->app->make(ApiClient::class);
            $query = [];
            if (!empty($this->statusFilter)) {
                $query['status'] = $this->statusFilter;
            }
            $result = $client->listInventory($query, $apiKey);
            if (!$result['success']) {
                $error = $result['message'] ?? t('Unable to load inventory.');
            } else {
                $items = $this->normalizeInventory($result['data'] ?? []);
                $max = max(1, (int) ($this->maxItems ?: 20));
                $items = array_slice($items, 0, $max);
            }
        }

        $this->set('inventoryItems', $items);
        $this->set('inventoryError', $error);
        $this->set('showPrice', (int) $this->showPrice === 1);
        $this->set('showPlan', (int) $this->showPlan === 1);
        $this->set('showAvailability', (int) $this->showAvailability === 1);
        $this->set('emptyMessage', $this->emptyMessage ?: t('No inventory is available right now.'));
    }

    public function save($args)
    {
        $args['apiKey'] = isset($args['apiKey']) ? trim((string) $args['apiKey']) : '';
        $args['statusFilter'] = isset($args['statusFilter']) ? trim((string) $args['statusFilter']) : '';
        $args['maxItems'] = isset($args['maxItems']) ? max(1, (int) $args['maxItems']) : 20;
        $args['showPrice'] = !empty($args['showPrice']) ? 1 : 0;
        $args['showPlan'] = !empty($args['showPlan']) ? 1 : 0;
        $args['showAvailability'] = !empty($args['showAvailability']) ? 1 : 0;
        $args['emptyMessage'] = isset($args['emptyMessage']) ? trim((string) $args['emptyMessage']) : '';

        parent::save($args);
    }

    public function validate($args)
    {
        $errors = $this->app->make(ErrorList::class);
        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        if ($config->resolveApiKey($args['apiKey'] ?? '') === '') {
            $errors->add(t('Configure a Lasso API key in package settings or provide a block override.'));
        }

        return $errors;
    }

    /**
     * @param mixed $data
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeInventory($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $raw = [];
        if (isset($data['inventory']) && is_array($data['inventory'])) {
            $raw = $data['inventory'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $raw = $data['items'];
        } elseif ($data !== [] && array_keys($data) === range(0, count($data) - 1)) {
            $raw = $data;
        }

        $items = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pricing = $row['pricing'] ?? [];
            $price = '';
            if (is_array($pricing)) {
                $price = $pricing['listPrice'] ?? ($pricing['price'] ?? ($pricing['basePrice'] ?? ''));
            }
            if ($price === '' && isset($row['price'])) {
                $price = $row['price'];
            }

            $plan = $row['planType'] ?? ($row['plan'] ?? '');
            if (is_array($plan)) {
                $plan = $plan['name'] ?? ($plan['planType'] ?? '');
            }

            $items[] = [
                'number' => (string) ($row['inventoryNumber'] ?? $row['number'] ?? $row['unitNumber'] ?? ''),
                'lot' => (string) ($row['strataLot'] ?? $row['lot'] ?? ''),
                'status' => (string) ($row['status'] ?? $row['availability'] ?? ''),
                'price' => is_scalar($price) ? (string) $price : '',
                'plan' => (string) $plan,
            ];
        }

        return $items;
    }
}
