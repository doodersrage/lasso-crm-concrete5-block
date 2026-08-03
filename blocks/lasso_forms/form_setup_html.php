<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="form-group">
    <label class="form-label" for="apiKey"><?= t('Lasso API Key') ?></label>
    <input class="form-control" type="password" name="apiKey" id="apiKey" value="<?= h($apiKey ?? '') ?>" autocomplete="off">
    <small class="form-text text-muted"><?= t('Project-scoped Bearer token from Lasso Data Systems.') ?></small>
</div>

<div class="form-group">
    <label class="form-label" for="signupThankYouLink"><?= t('Signup Thank You Link') ?></label>
    <textarea class="form-control" id="signupThankYouLink" name="signupThankYouLink" rows="3"><?= h($signupThankYouLink ?? '') ?></textarea>
    <small class="form-text text-muted"><?= t('Optional URL to redirect visitors after a successful submission.') ?></small>
</div>

<div class="form-group">
    <label class="form-label" for="thankYouEmailTemplateId"><?= t('Thank You Email Template ID') ?></label>
    <input class="form-control" type="text" name="thankYouEmailTemplateId" id="thankYouEmailTemplateId" value="<?= h($thankYouEmailTemplateId ?? '') ?>">
    <small class="form-text text-muted"><?= t('Optional Lasso auto-reply email template ID.') ?></small>
</div>

<h4><?= t('How Did You Learn About Us') ?></h4>

<div class="form-group">
    <label class="form-label" for="questionId"><?= t('Question ID') ?></label>
    <input class="form-control" type="text" name="questionId" id="questionId" value="<?= h($questionId ?? '') ?>">
    <small class="form-text text-muted"><?= t('Optional. Use a Lasso question ID to submit answers by ID.') ?></small>
</div>

<div class="form-group">
    <label class="form-label" for="questionName"><?= t('Question Label') ?></label>
    <input class="form-control" type="text" name="questionName" id="questionName" value="<?= h($questionName ?? '') ?>" placeholder="<?= t('How did you learn about us?') ?>">
    <small class="form-text text-muted"><?= t('Used when no question ID is configured.') ?></small>
</div>

<div class="form-group">
    <label class="form-label" for="questionAnswers"><?= t('Answer Options') ?></label>
    <textarea class="form-control" id="questionAnswers" name="questionAnswers" rows="6"><?= h($questionAnswers ?? '') ?></textarea>
    <small class="form-text text-muted"><?= t('One answer per line in the format [answerId] Answer label.') ?></small>
</div>
