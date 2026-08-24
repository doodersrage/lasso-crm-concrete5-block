<?php

namespace Concrete\Package\LassoCrm\Block\LassoForms;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockController;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Http\Request;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Package\LassoCrm\Data\UsStates;
use Concrete\Package\LassoCrm\Lasso\QuestionAnswerParser;
use Concrete\Package\LassoCrm\Lasso\RegistrantClient;
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
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 550;
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
        $this->set('questionOptions', $parser->parse($this->questionAnswers));
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

        if (empty(trim($args['apiKey'] ?? ''))) {
            $errors->add(t('A Lasso API key is required.'));
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

        if (empty($this->apiKey)) {
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

        /** @var RegistrantClient $client */
        $client = $this->app->make(RegistrantClient::class);
        $result = $client->createRegistrant($this->apiKey, $payload);

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
        ];
    }
}
