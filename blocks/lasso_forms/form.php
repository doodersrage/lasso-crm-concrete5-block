<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
?>

<div class="ccm-ui">
    <div class="mb-3">
        <?= $form->label('apiKey', t('Lasso API Key')) ?>
        <?= $form->password('apiKey', $apiKey ?? '', ['autocomplete' => 'off']) ?>
        <div class="form-text"><?= t('Project-scoped Bearer token from Lasso Data Systems.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('signupThankYouLink', t('Signup Thank You Link')) ?>
        <?= $form->textarea('signupThankYouLink', $signupThankYouLink ?? '', ['rows' => 3]) ?>
        <div class="form-text"><?= t('Optional URL to redirect visitors after a successful submission.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('thankYouEmailTemplateId', t('Thank You Email Template ID')) ?>
        <?= $form->text('thankYouEmailTemplateId', $thankYouEmailTemplateId ?? '') ?>
        <div class="form-text"><?= t('Optional Lasso auto-reply email template ID.') ?></div>
    </div>

    <h4><?= t('How Did You Learn About Us') ?></h4>

    <div class="mb-3">
        <?= $form->label('questionId', t('Question ID')) ?>
        <?= $form->text('questionId', $questionId ?? '') ?>
        <div class="form-text"><?= t('Optional. Use a Lasso question ID to submit answers by ID.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('questionName', t('Question Label')) ?>
        <?= $form->text('questionName', $questionName ?? '', ['placeholder' => t('How did you learn about us?')]) ?>
        <div class="form-text"><?= t('Used when no question ID is configured.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('questionAnswers', t('Answer Options')) ?>
        <?= $form->textarea('questionAnswers', $questionAnswers ?? '', ['rows' => 6]) ?>
        <div class="form-text"><?= t('One answer per line in the format [answerId] Answer label.') ?></div>
    </div>
</div>
