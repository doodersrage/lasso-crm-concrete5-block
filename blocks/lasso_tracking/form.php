<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
?>

<div class="ccm-ui">
    <div class="mb-3">
        <?= $form->label('accountId', t('Tracking Account ID Override (optional)')) ?>
        <?= $form->text('accountId', $accountId ?? '') ?>
        <div class="form-text">
            <?php if (!empty($packageTrackingAccountId)) { ?>
                <?= t('Package default:') ?> <?= h($packageTrackingAccountId) ?>
            <?php } else { ?>
                <?= t('Set a default under Dashboard → Lasso CRM → Settings, or enter an account ID here.') ?>
            <?php } ?>
        </div>
    </div>

    <?php if (!empty($globalTrackingEnabled)) { ?>
        <div class="alert alert-info mb-0">
            <?= t('Global tracking is already enabled in package settings. This block is only needed for page-specific overrides.') ?>
        </div>
    <?php } ?>
</div>
