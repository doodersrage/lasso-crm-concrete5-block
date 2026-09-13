<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var string $mode */
/** @var list<array<string, string>> $appointments */
/** @var string|null $listError */
/** @var \Concrete\Core\Error\ErrorList\ErrorList|null $errors */
/** @var array<string, string> $formData */
$formData = $formData ?? [];
$value = static function (string $key) use ($formData): string {
    return h($formData[$key] ?? '');
};
$mode = $mode ?? 'inquiry';
$showList = in_array($mode, ['list', 'both'], true);
$showForm = in_array($mode, ['inquiry', 'both'], true);
?>

<div class="lasso-crm-appointments">
    <?php if ($showList) { ?>
        <div class="lasso-crm-appointments-list mb-4">
            <h3><?= t('Upcoming Appointments') ?></h3>
            <?php if (!empty($listError)) { ?>
                <div class="alert alert-warning"><?= h($listError) ?></div>
            <?php } elseif (empty($appointments)) { ?>
                <p class="text-muted"><?= t('No upcoming appointments are available.') ?></p>
            <?php } else { ?>
                <ul class="list-unstyled">
                    <?php foreach ($appointments as $appointment) { ?>
                        <li class="mb-2">
                            <strong><?= h($appointment['subject'] ?? '') ?></strong>
                            <?php if (!empty($appointment['date'])) { ?>
                                — <?= h($appointment['date']) ?>
                            <?php } ?>
                            <?php if (!empty($appointment['time'])) { ?>
                                <?= h($appointment['time']) ?>
                            <?php } ?>
                            <?php if (!empty($appointment['status'])) { ?>
                                <span class="text-muted">(<?= h($appointment['status']) ?>)</span>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if ($showForm) { ?>
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
                <?php $token = app('token'); echo $token->output('lasso_appointment_submit'); ?>

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
                    <label class="form-label" for="preferredDate"><?= t('Preferred Date') ?></label>
                    <input class="form-control" type="date" name="preferredDate" id="preferredDate" value="<?= $value('preferredDate') ?>" required>
                </fieldset>

                <fieldset class="mb-3">
                    <label class="form-label" for="preferredTime"><?= t('Preferred Time') ?></label>
                    <input class="form-control" type="time" name="preferredTime" id="preferredTime" value="<?= $value('preferredTime') ?>">
                </fieldset>

                <fieldset class="mb-3">
                    <label class="form-label" for="comments"><?= t('Notes') ?></label>
                    <textarea class="form-control" name="comments" id="comments" rows="4"><?= $value('comments') ?></textarea>
                </fieldset>

                <button class="btn btn-primary" type="submit"><?= t('Request Appointment') ?></button>
            </form>
        <?php } ?>
    <?php } ?>
</div>
