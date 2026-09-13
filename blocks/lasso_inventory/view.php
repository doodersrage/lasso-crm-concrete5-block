<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var list<array<string, mixed>> $inventoryItems */
/** @var string|null $inventoryError */
/** @var bool $showPrice */
/** @var bool $showPlan */
/** @var bool $showAvailability */
/** @var string $emptyMessage */
$inventoryItems = $inventoryItems ?? [];
?>

<div class="lasso-crm-inventory">
    <?php if (!empty($inventoryError)) { ?>
        <div class="alert alert-warning"><?= h($inventoryError) ?></div>
    <?php } elseif (empty($inventoryItems)) { ?>
        <p class="text-muted"><?= h($emptyMessage) ?></p>
    <?php } else { ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?= t('Unit') ?></th>
                        <th><?= t('Lot') ?></th>
                        <?php if (!empty($showPlan)) { ?><th><?= t('Plan') ?></th><?php } ?>
                        <?php if (!empty($showAvailability)) { ?><th><?= t('Status') ?></th><?php } ?>
                        <?php if (!empty($showPrice)) { ?><th><?= t('Price') ?></th><?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventoryItems as $item) { ?>
                        <tr>
                            <td><?= h($item['number'] ?? '') ?></td>
                            <td><?= h($item['lot'] ?? '') ?></td>
                            <?php if (!empty($showPlan)) { ?><td><?= h($item['plan'] ?? '') ?></td><?php } ?>
                            <?php if (!empty($showAvailability)) { ?><td><?= h($item['status'] ?? '') ?></td><?php } ?>
                            <?php if (!empty($showPrice)) { ?><td><?= h($item['price'] ?? '') ?></td><?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</div>
