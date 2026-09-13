<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
?>

<div class="ccm-ui">
    <div class="mb-3">
        <?= $form->label('apiKey', t('API Key Override (optional)')) ?>
        <?= $form->password('apiKey', $apiKey ?? '', ['autocomplete' => 'off']) ?>
        <div class="form-text">
            <?php if (!empty($packageApiKeyConfigured)) { ?>
                <?= t('Uses the package API key unless overridden.') ?>
            <?php } else { ?>
                <?= t('Configure a package API key under Dashboard → Lasso CRM → Settings.') ?>
            <?php } ?>
        </div>
    </div>

    <div class="mb-3">
        <?= $form->label('statusFilter', t('Status Filter')) ?>
        <?= $form->text('statusFilter', $statusFilter ?? '', ['placeholder' => t('e.g. Available')]) ?>
        <div class="form-text"><?= t('Optional status value passed to the inventory API.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('maxItems', t('Maximum Items')) ?>
        <?= $form->number('maxItems', $maxItems ?? 20, ['min' => 1, 'max' => 200]) ?>
    </div>

    <div class="form-check mb-2">
        <?= $form->checkbox('showPrice', 1, !empty($showPrice)) ?>
        <?= $form->label('showPrice', t('Show price'), ['class' => 'form-check-label']) ?>
    </div>
    <div class="form-check mb-2">
        <?= $form->checkbox('showPlan', 1, !empty($showPlan)) ?>
        <?= $form->label('showPlan', t('Show plan'), ['class' => 'form-check-label']) ?>
    </div>
    <div class="form-check mb-3">
        <?= $form->checkbox('showAvailability', 1, !empty($showAvailability)) ?>
        <?= $form->label('showAvailability', t('Show availability/status'), ['class' => 'form-check-label']) ?>
    </div>

    <div class="mb-3">
        <?= $form->label('emptyMessage', t('Empty State Message')) ?>
        <?= $form->text('emptyMessage', $emptyMessage ?? '') ?>
    </div>
</div>
