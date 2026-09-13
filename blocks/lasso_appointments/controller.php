<?php

namespace Concrete\Package\LassoCrm\Block\LassoAppointments;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Package\LassoCrm\Lasso\ApiClient;
use Concrete\Package\LassoCrm\Lasso\ConnectionConfig;
use Concrete\Package\LassoCrm\Lasso\RegistrantPayloadBuilder;
use Concrete\Package\LassoCrm\Lasso\SubmissionValidator;

class Controller extends BlockController
{
    /** @var string|null */
    public $apiKey;

    /** @var string|null */
    public $mode;

    /** @var string|null */
    public $thankYouMessage;

    /** @var string|null */
    public $thankYouLink;

    /** @var string|null */
    public $rotationId;

    protected $btTable = 'btLassoAppointments';
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 520;
    protected $btDefaultSet = 'form';
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputOnPost = false;

    public function getBlockTypeName()
    {
        return t('Lasso Appointments');
    }

    public function getBlockTypeDescription()
    {
        return t('Collect appointment inquiries or list upcoming project appointments from Lasso CRM.');
    }

    public function add()
    {
        $this->formSetup();
        $this->set('mode', 'inquiry');
        $this->set('thankYouMessage', t('Thank you. Your appointment request has been received.'));
    }

    public function edit()
    {
        $this->formSetup();
    }

    protected function formSetup(): void
    {
        $this->set('form', $this->app->make('helper/form'));
        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        $this->set('packageApiKeyConfigured', $config->getApiKey() !== '');
    }

    public function view()
    {
        $this->requireAsset('css', 'core/frontend/errors');
        $mode = $this->mode ?: 'inquiry';
        $this->set('mode', $mode);

        $appointments = [];
        $listError = null;

        if ($mode === 'list' || $mode === 'both') {
            /** @var ConnectionConfig $config */
            $config = $this->app->make(ConnectionConfig::class);
            $apiKey = $config->resolveApiKey($this->apiKey);
            if ($apiKey === '') {
                $listError = t('Lasso API key is not configured.');
            } else {
                /** @var ApiClient $client */
                $client = $this->app->make(ApiClient::class);
                $result = $client->listProjectAppointments([], $apiKey);
                if (!$result['success']) {
                    $listError = $result['message'] ?? t('Unable to load appointments.');
                } else {
                    $appointments = $this->normalizeAppointments($result['data'] ?? []);
                }
            }
        }

        $this->set('appointments', $appointments);
        $this->set('listError', $listError);
        $this->set('thankYouMessage', $this->thankYouMessage ?: t('Thank you. Your appointment request has been received.'));
    }

    public function save($args)
    {
        $args['apiKey'] = isset($args['apiKey']) ? trim((string) $args['apiKey']) : '';
        $args['mode'] = in_array($args['mode'] ?? '', ['inquiry', 'list', 'both'], true) ? $args['mode'] : 'inquiry';
        $args['thankYouMessage'] = isset($args['thankYouMessage']) ? trim((string) $args['thankYouMessage']) : '';
        $args['thankYouLink'] = isset($args['thankYouLink']) ? trim((string) $args['thankYouLink']) : '';
        $args['rotationId'] = isset($args['rotationId']) ? trim((string) $args['rotationId']) : '';

        parent::save($args);
    }

    public function validate($args)
    {
        $errors = $this->app->make(ErrorList::class);
        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        if ($config->resolveApiKey($args['apiKey'] ?? '') === '') {
            $errors->add(t('Configure a Lasso API key in package settings or provide a block override.'));
        }

        return $errors;
    }

    public function action_submit($bID = false)
    {
        if ($this->bID != $bID) {
            return false;
        }

        $this->view();

        /** @var Token $token */
        $token = $this->app->make('token');
        if (!$token->validate('lasso_appointment_submit')) {
            $errors = $this->app->make(ErrorList::class);
            $errors->add($token->getErrorMessage());
            $this->set('errors', $errors);

            return;
        }

        /** @var Request $request */
        $request = $this->app->make(Request::class);
        $data = [
            'firstName' => trim((string) $request->request->get('firstName')),
            'lastName' => trim((string) $request->request->get('lastName')),
            'email' => trim((string) $request->request->get('email')),
            'phone' => trim((string) $request->request->get('phone')),
            'preferredDate' => trim((string) $request->request->get('preferredDate')),
            'preferredTime' => trim((string) $request->request->get('preferredTime')),
            'comments' => trim((string) $request->request->get('comments')),
            'questionAnswerId' => '',
            'websiteTracking' => trim((string) $request->cookies->get('ut')),
        ];

        /** @var SubmissionValidator $validator */
        $validator = $this->app->make(SubmissionValidator::class);
        $errors = $validator->validateContact($data);
        if ($data['preferredDate'] === '') {
            $errors->add(t('Preferred date is required.'));
        }

        if ($errors->has()) {
            $this->set('errors', $errors);
            $this->set('formData', $data);

            return;
        }

        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        $apiKey = $config->resolveApiKey($this->apiKey);

        $noteParts = [
            t('Appointment request'),
            t('Preferred date: %s', $data['preferredDate']),
        ];
        if ($data['preferredTime'] !== '') {
            $noteParts[] = t('Preferred time: %s', $data['preferredTime']);
        }
        if ($data['comments'] !== '') {
            $noteParts[] = $data['comments'];
        }
        $data['comments'] = implode("\n", $noteParts);

        /** @var RegistrantPayloadBuilder $payloadBuilder */
        $payloadBuilder = $this->app->make(RegistrantPayloadBuilder::class);
        $options = [];
        if (!empty($this->rotationId)) {
            $options['rotationId'] = $this->rotationId;
        }
        $payload = $payloadBuilder->build($data, null, null, null, null, $options);

        /** @var ApiClient $client */
        $client = $this->app->make(ApiClient::class);
        $createResult = $client->createRegistrant($payload, $apiKey);

        if (!$createResult['success']) {
            $errors = $this->app->make(ErrorList::class);
            $errors->add($createResult['message'] ?? t('Unable to submit appointment request.'));
            $this->set('errors', $errors);
            $this->set('formData', $data);

            return;
        }

        $registrantId = $this->extractRegistrantId($createResult['data'] ?? null);
        if ($registrantId !== '') {
            $appointmentPayload = [
                'subject' => t('Website appointment request'),
                'notes' => $data['comments'],
                'schedule' => [
                    'date' => $data['preferredDate'],
                    'time' => $data['preferredTime'],
                ],
            ];
            $apptResult = $client->createRegistrantAppointment($registrantId, $appointmentPayload, $apiKey);
            if (!$apptResult['success']) {
                $client->addRegistrantNote($registrantId, [
                    'note' => t('Appointment create failed; inquiry retained as note.') . "\n" . $data['comments'],
                ], $apiKey);
            }
        }

        if (!empty($this->thankYouLink)) {
            return $this->buildRedirect($this->thankYouLink);
        }

        $this->set('success', $this->thankYouMessage ?: t('Thank you. Your appointment request has been received.'));
    }

    /**
     * @param mixed $data
     *
     * @return list<array<string, string>>
     */
    private function normalizeAppointments($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $raw = [];
        if (isset($data['appointments']) && is_array($data['appointments'])) {
            $raw = $data['appointments'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $raw = $data['items'];
        } elseif ($data !== [] && array_keys($data) === range(0, count($data) - 1)) {
            $raw = $data;
        }

        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $schedule = is_array($row['schedule'] ?? null) ? $row['schedule'] : [];
            $out[] = [
                'subject' => (string) ($row['subject'] ?? $row['title'] ?? t('Appointment')),
                'date' => (string) ($row['date'] ?? ($schedule['date'] ?? ($row['start'] ?? ''))),
                'time' => (string) ($row['time'] ?? ($schedule['time'] ?? '')),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param mixed $data
     */
    private function extractRegistrantId($data): string
    {
        if (!is_array($data)) {
            return '';
        }

        foreach (['registrantId', 'id', 'lassoRegistrantId'] as $key) {
            if (!empty($data[$key]) && is_scalar($data[$key])) {
                return (string) $data[$key];
            }
        }

        if (isset($data['registrant']) && is_array($data['registrant'])) {
            return $this->extractRegistrantId($data['registrant']);
        }

        return '';
    }
}
