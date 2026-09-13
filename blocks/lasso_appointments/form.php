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
        <?= $form->label('mode', t('Display Mode')) ?>
        <?= $form->select('mode', [
            'inquiry' => t('Inquiry form'),
            'list' => t('Upcoming appointments list'),
            'both' => t('List and inquiry form'),
        ], $mode ?? 'inquiry') ?>
    </div>

    <div class="mb-3">
        <?= $form->label('rotationId', t('Rotation ID (optional)')) ?>
        <?= $form->text('rotationId', $rotationId ?? '') ?>
        <div class="form-text"><?= t('Assign inquiries using a Lasso sales-rep rotation.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('thankYouMessage', t('Thank You Message')) ?>
        <?= $form->textarea('thankYouMessage', $thankYouMessage ?? '', ['rows' => 3]) ?>
    </div>

    <div class="mb-3">
        <?= $form->label('thankYouLink', t('Thank You Link')) ?>
        <?= $form->text('thankYouLink', $thankYouLink ?? '') ?>
        <div class="form-text"><?= t('Optional redirect URL after a successful inquiry.') ?></div>
    </div>
</div>
