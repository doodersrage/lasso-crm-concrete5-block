<?php defined('C5_EXECUTE') or die('Access Denied.');

/** @var bool $hasApiKey */
/** @var bool $connectionOk */
/** @var string $connectionMessage */
/** @var string $lastSuccessfulPing */
/** @var string $trackingAccountId */
/** @var bool $globalTrackingEnabled */
/** @var string $defaultSourceType */
/** @var array|null $projectSettings */
/** @var list<array<string, mixed>> $registrants */
?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?= \Concrete\Core\Support\Facade\Url::to('/dashboard/lasso_crm/settings') ?>" class="btn btn-primary"><?= t('Settings') ?></a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><?= t('Connection') ?></div>
            <div class="card-body">
                <?php if ($connectionOk) { ?>
                    <p class="text-success mb-2"><strong><?= h($connectionMessage) ?></strong></p>
                <?php } else { ?>
                    <p class="text-danger mb-2"><strong><?= h($connectionMessage) ?></strong></p>
                <?php } ?>
                <ul class="mb-0">
                    <li><?= t('API key:') ?> <?= $hasApiKey ? t('Configured') : t('Not set') ?></li>
                    <li><?= t('Last successful ping:') ?> <?= $lastSuccessfulPing !== '' ? h($lastSuccessfulPing) : t('Never') ?></li>
                    <li><?= t('Tracking account:') ?> <?= $trackingAccountId !== '' ? h($trackingAccountId) : t('Not set') ?></li>
                    <li><?= t('Global tracking:') ?> <?= !empty($globalTrackingEnabled) ? t('Enabled') : t('Disabled') ?></li>
                    <li><?= t('Default source type:') ?> <?= h($defaultSourceType) ?></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header"><?= t('Project settings snapshot') ?></div>
            <div class="card-body">
                <?php if (is_array($projectSettings)) { ?>
                    <?php
                    $projectName = $projectSettings['project']['name']
                        ?? $projectSettings['project']['projectName']
                        ?? null;
                    $questionCount = isset($projectSettings['questions']) && is_array($projectSettings['questions'])
                        ? count($projectSettings['questions'])
                        : 0;
                    $sourceCount = isset($projectSettings['sourceTypes']) && is_array($projectSettings['sourceTypes'])
                        ? count($projectSettings['sourceTypes'])
                        : (isset($projectSettings['source_types']) && is_array($projectSettings['source_types'])
                            ? count($projectSettings['source_types'])
                            : 0);
                    $ratingCount = isset($projectSettings['ratings']) && is_array($projectSettings['ratings'])
                        ? count($projectSettings['ratings'])
                        : 0;
                    ?>
                    <ul class="mb-0">
                        <li><?= t('Project:') ?> <?= $projectName ? h($projectName) : t('Available') ?></li>
                        <li><?= t('Questions:') ?> <?= (int) $questionCount ?></li>
                        <li><?= t('Source types:') ?> <?= (int) $sourceCount ?></li>
                        <li><?= t('Ratings:') ?> <?= (int) $ratingCount ?></li>
                    </ul>
                <?php } else { ?>
                    <p class="text-muted mb-0"><?= t('Project settings will appear after a successful connection.') ?></p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><?= t('Recent registrants') ?></div>
    <div class="card-body">
        <?php if (empty($registrants)) { ?>
            <p class="text-muted mb-0"><?= t('No registrants loaded. Check your API key or try again later.') ?></p>
        <?php } else { ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th><?= t('Name') ?></th>
                            <th><?= t('Email') ?></th>
                            <th><?= t('Registered') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrants as $registrant) {
                            $person = $registrant['person'] ?? [];
                            $first = $person['firstName'] ?? ($registrant['firstName'] ?? '');
                            $last = $person['lastName'] ?? ($registrant['lastName'] ?? '');
                            $emails = $registrant['emails'] ?? [];
                            $email = '';
                            if (is_array($emails) && isset($emails[0]['email'])) {
                                $email = $emails[0]['email'];
                            } elseif (!empty($registrant['email'])) {
                                $email = $registrant['email'];
                            }
                            $registered = $registrant['registrationDate'] ?? ($registrant['created'] ?? '');
                            ?>
                            <tr>
                                <td><?= h(trim($first . ' ' . $last)) ?></td>
                                <td><?= h($email) ?></td>
                                <td><?= h((string) $registered) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</div>
