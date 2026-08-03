<?php

namespace Concrete\Package\LassoCrm\Block\LassoForms;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Http\Request;
use Concrete\Core\Validation\CSRF\Token;

class Controller extends BlockController
{
    protected $btTable = 'btLMSBlockContent';
    protected $btInterfaceWidth = '600';
    protected $btInterfaceHeight = '550';
    protected $btDefaultSet = 'form';

    protected $apiKey;
    protected $signupThankYouLink;
    protected $thankYouEmailTemplateId;
    protected $questionId;
    protected $questionName;
    protected $questionAnswers;

    private const LASSO_API_URL = 'https://api.lassocrm.com/v1/registrants';

    public function getBlockTypeName()
    {
        return t('Lasso CRM Form');
    }

    public function getBlockTypeDescription()
    {
        return t('Submit registrant leads to Lasso CRM via the REST API.');
    }

    public function view()
    {
        $this->set('questionOptions', $this->parseQuestionAnswers($this->questionAnswers));
        $this->set('usStates', $this->getUsStates());
        $this->set('questionName', $this->questionName ?: t('How did you learn about us?'));
    }

    public function add()
    {
        $this->edit();
    }

    public function edit()
    {
        $this->set('apiKey', $this->apiKey);
        $this->set('signupThankYouLink', $this->signupThankYouLink);
        $this->set('thankYouEmailTemplateId', $this->thankYouEmailTemplateId);
        $this->set('questionId', $this->questionId);
        $this->set('questionName', $this->questionName);
        $this->set('questionAnswers', $this->questionAnswers);
    }

    public function save($args)
    {
        $args['apiKey'] = isset($args['apiKey']) ? trim($args['apiKey']) : '';
        $args['signupThankYouLink'] = isset($args['signupThankYouLink']) ? trim($args['signupThankYouLink']) : '';
        $args['thankYouEmailTemplateId'] = isset($args['thankYouEmailTemplateId']) ? trim($args['thankYouEmailTemplateId']) : '';
        $args['questionId'] = isset($args['questionId']) ? trim($args['questionId']) : '';
        $args['questionName'] = isset($args['questionName']) ? trim($args['questionName']) : '';
        $args['questionAnswers'] = isset($args['questionAnswers']) ? trim($args['questionAnswers']) : '';

        parent::save($args);
    }

    public function validate($args)
    {
        $e = $this->app->make('helper/validation/error');

        if (empty(trim($args['apiKey'] ?? ''))) {
            $e->add(t('A Lasso API key is required.'));
        }

        return $e;
    }

    public function action_submit($bID = false)
    {
        if ($this->bID != $bID) {
            return false;
        }

        /** @var Token $token */
        $token = $this->app->make('token');
        if (!$token->validate('lasso_form_submit')) {
            $this->set('error', t('Invalid form token. Please try again.'));
            $this->view();

            return;
        }

        /** @var Request $request */
        $request = $this->app->make(Request::class);
        $data = [
            'firstName' => trim((string) $request->request->get('firstName')),
            'lastName' => trim((string) $request->request->get('lastName')),
            'email' => trim((string) $request->request->get('email')),
            'phone' => trim((string) $request->request->get('phone')),
            'address' => trim((string) $request->request->get('address')),
            'city' => trim((string) $request->request->get('city')),
            'state' => trim((string) $request->request->get('state')),
            'postalCode' => trim((string) $request->request->get('postalCode')),
            'questionAnswerId' => trim((string) $request->request->get('questionAnswerId')),
            'comments' => trim((string) $request->request->get('comments')),
        ];

        $validationError = $this->validateSubmission($data);
        if ($validationError !== null) {
            $this->set('error', $validationError);
            $this->set('formData', $data);
            $this->view();

            return;
        }

        $result = $this->submitToLasso($data);
        if (!$result['success']) {
            $this->set('error', $result['message']);
            $this->set('formData', $data);
            $this->view();

            return;
        }

        if (!empty($this->signupThankYouLink)) {
            return $this->buildRedirect($this->signupThankYouLink);
        }

        $this->set('success', t('Thank you. Your information has been submitted.'));
        $this->view();
    }

    private function validateSubmission(array $data): ?string
    {
        if ($data['firstName'] === '' || $data['lastName'] === '') {
            return t('First name and last name are required.');
        }

        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return t('A valid email address is required.');
        }

        if ($data['postalCode'] === '') {
            return t('Zip code is required.');
        }

        return null;
    }

    private function submitToLasso(array $data): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => t('This form is not configured with a Lasso API key.'),
            ];
        }

        $payload = $this->buildRegistrantPayload($data);
        $json = json_encode($payload);

        if ($json === false) {
            return [
                'success' => false,
                'message' => t('Unable to prepare the submission payload.'),
            ];
        }

        $curl = curl_init(self::LASSO_API_URL);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $responseBody = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($responseBody === false) {
            return [
                'success' => false,
                'message' => t('Unable to connect to Lasso CRM.') . ($curlError ? ' ' . $curlError : ''),
            ];
        }

        if ($httpCode === 201) {
            return ['success' => true];
        }

        $decoded = json_decode($responseBody, true);
        $message = t('Lasso CRM rejected the submission.');

        if (is_array($decoded)) {
            if (!empty($decoded['message'])) {
                $message = (string) $decoded['message'];
            } elseif (!empty($decoded['error'])) {
                $message = is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']);
            }
        }

        return [
            'success' => false,
            'message' => $message . ' (' . t('HTTP %s', $httpCode) . ')',
        ];
    }

    private function buildRegistrantPayload(array $data): array
    {
        $payload = [
            'person' => [
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
            ],
            'emails' => [[
                'email' => $data['email'],
                'type' => 'Home',
                'primary' => true,
            ]],
            'sourceType' => [
                'sourceType' => 'Online Registration',
            ],
            'sendSalesRepAssignmentNotification' => true,
        ];

        if ($data['phone'] !== '') {
            $payload['phones'] = [[
                'phone' => $data['phone'],
                'type' => 'Home',
                'primary' => true,
            ]];
        }

        if ($data['address'] !== '' || $data['city'] !== '' || $data['state'] !== '' || $data['postalCode'] !== '') {
            $payload['addresses'] = [[
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zipCode' => $data['postalCode'],
                'country' => 'USA',
                'type' => 'Home',
                'primary' => true,
            ]];
        }

        if ($data['comments'] !== '') {
            $payload['notes'] = [[
                'note' => $data['comments'],
            ]];
        }

        if (!empty($this->thankYouEmailTemplateId)) {
            $payload['thankYouEmailTemplateId'] = $this->thankYouEmailTemplateId;
        }

        $question = $this->buildQuestionPayload($data['questionAnswerId']);
        if ($question !== null) {
            $payload['questions'] = [$question];
        }

        return $payload;
    }

    private function buildQuestionPayload(string $selectedAnswerId): ?array
    {
        if ($selectedAnswerId === '') {
            return null;
        }

        if (!empty($this->questionId)) {
            return [
                'questionId' => $this->questionId,
                'answers' => [[
                    'answerId' => $selectedAnswerId,
                ]],
            ];
        }

        if (empty($this->questionName)) {
            return null;
        }

        $options = $this->parseQuestionAnswers($this->questionAnswers);
        $selectedLabel = $options[$selectedAnswerId] ?? $selectedAnswerId;

        return [
            'name' => $this->questionName,
            'type' => 'checkbox',
            'answers' => [[
                'answer' => $selectedLabel,
            ]],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseQuestionAnswers(?string $answers): array
    {
        $options = [];

        if (empty($answers)) {
            return $options;
        }

        foreach (preg_split('/\r\n|\r|\n/', $answers) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\[([^\]]+)\]\s*(.*)$/', $line, $matches)) {
                $options[$matches[1]] = trim($matches[2]);
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function getUsStates(): array
    {
        return [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'DC' => 'Dist of Columbia',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            'AS' => 'American Samoa',
            'GU' => 'Guam',
            'MP' => 'Northern Mariana Islands',
            'PR' => 'Puerto Rico',
            'UM' => 'United States Minor Outlying Islands',
            'VI' => 'Virgin Islands',
            'AA' => 'Armed Forces Americas',
            'AP' => 'Armed Forces Pacific',
            'AE' => 'Armed Forces Others',
        ];
    }
}
