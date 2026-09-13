<?php

namespace Concrete\Package\LassoCrm\Block\LassoForms;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Package\LassoCrm\Data\UsStates;
use Concrete\Package\LassoCrm\Lasso\ApiClient;
use Concrete\Package\LassoCrm\Lasso\ConnectionConfig;
use Concrete\Package\LassoCrm\Lasso\QuestionAnswerParser;
use Concrete\Package\LassoCrm\Lasso\RegistrantPayloadBuilder;
use Concrete\Package\LassoCrm\Lasso\SubmissionValidator;

class Controller extends BlockController
{
    /** @var string|null */
    public $apiKey;

    /** @var string|null */
    public $signupThankYouLink;

    /** @var string|null */
    public $thankYouEmailTemplateId;

    /** @var string|null */
    public $questionId;

    /** @var string|null */
    public $questionName;

    /** @var string|null */
    public $questionAnswers;

    protected $btTable = 'btLassoForms';
    protected $btInterfaceWidth = 700;
    protected $btInterfaceHeight = 650;
    protected $btDefaultSet = 'form';
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputOnPost = false;

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
        $this->requireAsset('css', 'core/frontend/errors');

        $parser = $this->app->make(QuestionAnswerParser::class);
        $questionOptions = $parser->parse($this->questionAnswers);

        if (empty($questionOptions) && !empty($this->questionId)) {
            $questionOptions = $this->loadQuestionAnswersFromApi((string) $this->questionId);
        }

        $this->set('questionOptions', $questionOptions);
        $this->set('usStates', UsStates::all());
        $this->set('questionName', $this->questionName ?: t('How did you learn about us?'));
    }

    public function add()
    {
        $this->formSetup();
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
        $this->set('packageThankYouTemplateId', $config->getThankYouEmailTemplateId());
        $this->set('packageDefaultSourceType', $config->getDefaultSourceType());

        $projectQuestions = [];
        $projectSourceTypes = [];
        $apiKey = $config->resolveApiKey($this->apiKey);
        if ($apiKey !== '') {
            /** @var ApiClient $client */
            $client = $this->app->make(ApiClient::class);
            $result = $client->getProjectSettings($apiKey);
            if ($result['success'] && is_array($result['data'])) {
                $projectQuestions = $this->normalizeQuestions($result['data']);
                $projectSourceTypes = $this->normalizeSourceTypes($result['data']);
            }
        }

        $this->set('projectQuestions', $projectQuestions);
        $this->set('projectSourceTypes', $projectSourceTypes);
    }

    public function save($args)
    {
        $args['apiKey'] = isset($args['apiKey']) ? trim((string) $args['apiKey']) : '';
        $args['signupThankYouLink'] = isset($args['signupThankYouLink']) ? trim((string) $args['signupThankYouLink']) : '';
        $args['thankYouEmailTemplateId'] = isset($args['thankYouEmailTemplateId']) ? trim((string) $args['thankYouEmailTemplateId']) : '';
        $args['questionId'] = isset($args['questionId']) ? trim((string) $args['questionId']) : '';
        $args['questionName'] = isset($args['questionName']) ? trim((string) $args['questionName']) : '';
        $args['questionAnswers'] = isset($args['questionAnswers']) ? trim((string) $args['questionAnswers']) : '';

        parent::save($args);
    }

    public function validate($args)
    {
        $errors = $this->app->make(ErrorList::class);

        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        $resolved = $config->resolveApiKey($args['apiKey'] ?? '');

        if ($resolved === '') {
            $errors->add(t('Configure a Lasso API key in Dashboard → Lasso CRM → Settings, or enter a block-level override.'));
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
        if (!$token->validate('lasso_form_submit')) {
            $errors = $this->app->make(ErrorList::class);
            $errors->add($token->getErrorMessage());
            $this->set('errors', $errors);

            return;
        }

        $data = $this->getSubmissionData();

        /** @var SubmissionValidator $validator */
        $validator = $this->app->make(SubmissionValidator::class);
        $errors = $validator->validate($data);

        if ($errors->has()) {
            $this->set('errors', $errors);
            $this->set('formData', $data);

            return;
        }

        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        $apiKey = $config->resolveApiKey($this->apiKey);

        if ($apiKey === '') {
            $errors = $this->app->make(ErrorList::class);
            $errors->add(t('This form is not configured with a Lasso API key.'));
            $this->set('errors', $errors);
            $this->set('formData', $data);

            return;
        }

        /** @var RegistrantPayloadBuilder $payloadBuilder */
        $payloadBuilder = $this->app->make(RegistrantPayloadBuilder::class);
        $payload = $payloadBuilder->build(
            $data,
            $this->questionId,
            $this->questionName,
            $this->questionAnswers,
            $this->thankYouEmailTemplateId
        );

        /** @var ApiClient $client */
        $client = $this->app->make(ApiClient::class);
        $result = $client->createRegistrant($payload, $apiKey);

        if (!$result['success']) {
            $errors = $this->app->make(ErrorList::class);
            $errors->add($result['message'] ?? t('Unable to submit to Lasso CRM.'));
            $this->set('errors', $errors);
            $this->set('formData', $data);

            return;
        }

        if (!empty($this->signupThankYouLink)) {
            return $this->buildRedirect($this->signupThankYouLink);
        }

        $this->set('success', t('Thank you. Your information has been submitted.'));
    }

    /**
     * @return array<string, string>
     */
    private function getSubmissionData(): array
    {
        /** @var Request $request */
        $request = $this->app->make(Request::class);

        $tracking = trim((string) $request->request->get('websiteTracking'));
        if ($tracking === '') {
            $tracking = trim((string) $request->cookies->get('ut'));
        }

        return [
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
            'websiteTracking' => $tracking,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function loadQuestionAnswersFromApi(string $questionId): array
    {
        /** @var ConnectionConfig $config */
        $config = $this->app->make(ConnectionConfig::class);
        $apiKey = $config->resolveApiKey($this->apiKey);
        if ($apiKey === '') {
            return [];
        }

        /** @var ApiClient $client */
        $client = $this->app->make(ApiClient::class);
        $result = $client->getProjectSettings($apiKey);
        if (!$result['success'] || !is_array($result['data'])) {
            return [];
        }

        foreach ($this->normalizeQuestions($result['data']) as $question) {
            if ((string) ($question['id'] ?? '') === $questionId) {
                return $question['answers'] ?? [];
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return list<array{id: string, name: string, answers: array<string, string>}>
     */
    private function normalizeQuestions(array $settings): array
    {
        $raw = $settings['questions'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $question) {
            if (!is_array($question)) {
                continue;
            }
            $id = (string) ($question['questionId'] ?? $question['id'] ?? '');
            $name = (string) ($question['name'] ?? $question['question'] ?? $id);
            $answers = [];
            $rawAnswers = $question['answers'] ?? [];
            if (is_array($rawAnswers)) {
                foreach ($rawAnswers as $answer) {
                    if (!is_array($answer)) {
                        continue;
                    }
                    $answerId = (string) ($answer['answerId'] ?? $answer['id'] ?? '');
                    $label = (string) ($answer['answer'] ?? $answer['text'] ?? $answerId);
                    if ($answerId !== '') {
                        $answers[$answerId] = $label;
                    }
                }
            }
            if ($id !== '') {
                $out[] = [
                    'id' => $id,
                    'name' => $name,
                    'answers' => $answers,
                    'answersText' => $this->formatAnswersForEditor($answers),
                ];
            }
        }

        return $out;
    }

    /**
     * @param array<string, string> $answers
     */
    public function formatAnswersForEditor(array $answers): string
    {
        $lines = [];
        foreach ($answers as $answerId => $label) {
            $lines[] = '[' . $answerId . '] ' . $label;
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return list<string>
     */
    private function normalizeSourceTypes(array $settings): array
    {
        $raw = $settings['sourceTypes'] ?? ($settings['source_types'] ?? []);
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $out[] = $item;
            } elseif (is_array($item)) {
                $label = $item['sourceType'] ?? ($item['name'] ?? null);
                if (is_string($label) && $label !== '') {
                    $out[] = $label;
                }
            }
        }

        return $out;
    }
}
