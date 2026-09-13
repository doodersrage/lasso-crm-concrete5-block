<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
$form = app('helper/form');
/** @var bool $apiKeyConfigured */
/** @var string $trackingAccountId */
/** @var string $thankYouEmailTemplateId */
/** @var string $defaultSourceType */
/** @var bool $globalTrackingEnabled */
/** @var string $lastSuccessfulPing */
?>

<form method="post" action="<?= $view->action('save') ?>">
    <?= $token->output('lasso_crm_settings') ?>

    <fieldset>
        <legend><?= t('API Connection') ?></legend>

        <div class="form-group">
            <?= $form->label('api_key', t('Lasso API Key')) ?>
            <?= $form->password('api_key', '', ['autocomplete' => 'off', 'placeholder' => $apiKeyConfigured ? t('(leave blank to keep current key)') : '']) ?>
            <div class="help-block">
                <?= t('Project-scoped Bearer token from Lasso Data Systems.') ?>
                <?php if ($apiKeyConfigured) { ?>
                    <?= t('A key is already saved.') ?>
                <?php } ?>
            </div>
        </div>

        <div class="form-group">
            <?= $form->label('default_source_type', t('Default Source Type')) ?>
            <?= $form->text('default_source_type', $defaultSourceType) ?>
        </div>

        <div class="form-group">
            <?= $form->label('thank_you_email_template_id', t('Default Thank You Email Template ID')) ?>
            <?= $form->text('thank_you_email_template_id', $thankYouEmailTemplateId) ?>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= t('Website Tracking') ?></legend>

        <div class="form-group">
            <?= $form->label('tracking_account_id', t('Tracking Account ID')) ?>
            <?= $form->text('tracking_account_id', $trackingAccountId) ?>
            <div class="help-block"><?= t('Lasso Analytics account ID used by the website tracker.') ?></div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <?= $form->checkbox('global_tracking_enabled', 1, $globalTrackingEnabled) ?>
                <?= $form->label('global_tracking_enabled', t('Inject Lasso Analytics on all public pages'), ['class' => 'form-check-label']) ?>
            </div>
        </div>
    </fieldset>

    <?php if ($lastSuccessfulPing !== '') { ?>
        <p class="text-muted"><?= t('Last successful connection:') ?> <?= h($lastSuccessfulPing) ?></p>
    <?php } ?>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button type="submit" class="btn btn-primary float-end"><?= t('Save') ?></button>
        </div>
    </div>
</form>

<form method="post" action="<?= $view->action('test_connection') ?>" class="mt-3">
    <?= $token->output('lasso_crm_test_connection') ?>
    <button type="submit" class="btn btn-secondary"><?= t('Test Connection') ?></button>
</form>
