<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
/** @var bool $packageApiKeyConfigured */
/** @var list<array{id: string, name: string, answers: array<string, string>}> $projectQuestions */
/** @var list<string> $projectSourceTypes */
$projectQuestions = $projectQuestions ?? [];
$projectSourceTypes = $projectSourceTypes ?? [];
?>

<div class="ccm-ui">
    <div class="mb-3">
        <?= $form->label('apiKey', t('API Key Override (optional)')) ?>
        <?= $form->password('apiKey', $apiKey ?? '', ['autocomplete' => 'off']) ?>
        <div class="form-text">
            <?php if (!empty($packageApiKeyConfigured)) { ?>
                <?= t('Uses the package API key from Dashboard → Lasso CRM → Settings unless you override it here.') ?>
            <?php } else { ?>
                <?= t('No package API key is configured. Set one under Dashboard → Lasso CRM → Settings, or enter a key here.') ?>
            <?php } ?>
        </div>
    </div>

    <div class="mb-3">
        <?= $form->label('signupThankYouLink', t('Signup Thank You Link')) ?>
        <?= $form->textarea('signupThankYouLink', $signupThankYouLink ?? '', ['rows' => 3]) ?>
        <div class="form-text"><?= t('Optional URL to redirect visitors after a successful submission.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('thankYouEmailTemplateId', t('Thank You Email Template ID')) ?>
        <?= $form->text('thankYouEmailTemplateId', $thankYouEmailTemplateId ?? '') ?>
        <div class="form-text">
            <?= t('Optional. Falls back to the package default template ID when blank.') ?>
            <?php if (!empty($packageThankYouTemplateId)) { ?>
                (<?= t('Package default:') ?> <?= h($packageThankYouTemplateId) ?>)
            <?php } ?>
        </div>
    </div>

    <h4><?= t('How Did You Learn About Us') ?></h4>

    <?php if (!empty($projectQuestions)) { ?>
        <div class="mb-3">
            <?= $form->label('projectQuestionSelect', t('Load from Lasso project questions')) ?>
            <select class="form-select" id="projectQuestionSelect">
                <option value=""><?= t('Select a question…') ?></option>
                <?php foreach ($projectQuestions as $question) { ?>
                    <option
                        value="<?= h($question['id']) ?>"
                        data-name="<?= h($question['name']) ?>"
                        data-answers="<?= h($question['answersText'] ?? '') ?>"
                    ><?= h($question['name']) ?> (<?= h($question['id']) ?>)</option>
                <?php } ?>
            </select>
            <div class="form-text"><?= t('Selecting a question fills Question ID, label, and answer options below.') ?></div>
        </div>
    <?php } ?>

    <div class="mb-3">
        <?= $form->label('questionId', t('Question ID')) ?>
        <?= $form->text('questionId', $questionId ?? '', ['id' => 'questionId']) ?>
        <div class="form-text"><?= t('Optional. Use a Lasso question ID to submit answers by ID.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('questionName', t('Question Label')) ?>
        <?= $form->text('questionName', $questionName ?? '', ['id' => 'questionName', 'placeholder' => t('How did you learn about us?')]) ?>
        <div class="form-text"><?= t('Used when no question ID is configured.') ?></div>
    </div>

    <div class="mb-3">
        <?= $form->label('questionAnswers', t('Answer Options')) ?>
        <?= $form->textarea('questionAnswers', $questionAnswers ?? '', ['id' => 'questionAnswers', 'rows' => 6]) ?>
        <div class="form-text"><?= t('One answer per line in the format [answerId] Answer label.') ?></div>
    </div>

    <?php if (!empty($projectSourceTypes)) { ?>
        <div class="alert alert-info">
            <?= t('Available source types from Lasso:') ?>
            <?= h(implode(', ', $projectSourceTypes)) ?>
            <div class="form-text mb-0"><?= t('Default source type is configured under Dashboard → Lasso CRM → Settings.') ?> (<?= h($packageDefaultSourceType ?? '') ?>)</div>
        </div>
    <?php } ?>
</div>

<script>
(function () {
    var select = document.getElementById('projectQuestionSelect');
    if (!select) {
        return;
    }
    select.addEventListener('change', function () {
        var option = select.options[select.selectedIndex];
        if (!option || !option.value) {
            return;
        }
        var idField = document.getElementById('questionId');
        var nameField = document.getElementById('questionName');
        var answersField = document.getElementById('questionAnswers');
        if (idField) {
            idField.value = option.value;
        }
        if (nameField) {
            nameField.value = option.getAttribute('data-name') || '';
        }
        if (answersField) {
            answersField.value = option.getAttribute('data-answers') || '';
        }
    });
})();
</script>
