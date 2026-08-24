<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Error\ErrorList\ErrorList|null $errors */
/** @var array<string, string> $formData */
$formData = $formData ?? [];
$value = static function (string $key) use ($formData): string {
    return h($formData[$key] ?? '');
};
?>

<div class="lasso-crm-form">
    <?php if (!empty($success)) { ?>
        <div class="alert alert-success"><?= h($success) ?></div>
    <?php } ?>

    <?php if (isset($errors) && $errors->has()) { ?>
        <div class="ccm-system-errors alert alert-danger">
            <?= $errors->output() ?>
        </div>
    <?php } ?>

    <?php if (empty($success)) { ?>
        <form method="post" action="<?= $view->action('submit') ?>">
            <?php $token = app('token'); echo $token->output('lasso_form_submit'); ?>

            <fieldset class="mb-3">
                <label class="form-label" for="firstName"><?= t('First Name') ?></label>
                <input class="form-control" type="text" name="firstName" id="firstName" value="<?= $value('firstName') ?>" required>
            </fieldset>

            <fieldset class="mb-3">
                <label class="form-label" for="lastName"><?= t('Last Name') ?></label>
                <input class="form-control" type="text" name="lastName" id="lastName" value="<?= $value('lastName') ?>" required>
            </fieldset>

            <fieldset class="mb-3">
                <label class="form-label" for="email"><?= t('Email Address') ?></label>
                <input class="form-control" type="email" name="email" id="email" value="<?= $value('email') ?>" required>
            </fieldset>

            <fieldset class="mb-3">
                <label class="form-label" for="phone"><?= t('Phone Number') ?></label>
                <input class="form-control" type="tel" name="phone" id="phone" value="<?= $value('phone') ?>">
            </fieldset>

            <fieldset class="mb-3">
                <label class="form-label" for="address"><?= t('Address') ?></label>
                <input class="form-control" type="text" name="address" id="address" value="<?= $value('address') ?>">
            </fieldset>

            <fieldset class="mb-3">
                <label class="form-label" for="city"><?= t('City') ?></label>
                <input class="form-control" type="text" name="city" id="city" value="<?= $value('city') ?>">
            </fieldset>

            <fieldset class="mb-3">
                <label class="form-label" for="state"><?= t('State') ?></label>
                <select class="form-select" name="state" id="state">
                    <option value=""><?= t('Select a state') ?></option>
                    <?php foreach ($usStates as $code => $name) { ?>
                        <option value="<?= h($code) ?>" <?= ($formData['state'] ?? '') === $code ? 'selected' : '' ?>>
                            <?= h($name) ?>
                        </option>
                    <?php } ?>
                </select>
            </fieldset>

            <fieldset class="mb-3">
                <label class="form-label" for="postalCode"><?= t('Zip Code') ?></label>
                <input class="form-control" type="text" name="postalCode" id="postalCode" value="<?= $value('postalCode') ?>" required>
            </fieldset>

            <?php if (!empty($questionOptions)) { ?>
                <fieldset class="mb-3">
                    <label class="form-label" for="questionAnswerId"><?= h($questionName) ?></label>
                    <select class="form-select" name="questionAnswerId" id="questionAnswerId">
                        <option value=""><?= t('Select an option') ?></option>
                        <?php foreach ($questionOptions as $answerId => $label) { ?>
                            <option value="<?= h($answerId) ?>" <?= ($formData['questionAnswerId'] ?? '') === (string) $answerId ? 'selected' : '' ?>>
                                <?= h($label) ?>
                            </option>
                        <?php } ?>
                    </select>
                </fieldset>
            <?php } ?>

            <fieldset class="mb-3">
                <label class="form-label" for="comments"><?= t('Comments or Questions?') ?></label>
                <textarea class="form-control" name="comments" id="comments" rows="4"><?= $value('comments') ?></textarea>
            </fieldset>

            <button class="btn btn-primary" type="submit"><?= t('Submit') ?></button>
        </form>
    <?php } ?>
</div>
