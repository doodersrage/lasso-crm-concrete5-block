<?php

namespace Concrete\Package\LassoCrm\Lasso;

class RegistrantPayloadBuilder
{
    /** @var QuestionAnswerParser */
    private $questionAnswerParser;

    /** @var ConnectionConfig */
    private $connectionConfig;

    public function __construct(QuestionAnswerParser $questionAnswerParser, ConnectionConfig $connectionConfig)
    {
        $this->questionAnswerParser = $questionAnswerParser;
        $this->connectionConfig = $connectionConfig;
    }

    /**
     * @param array<string, string> $data
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function build(array $data, ?string $questionId, ?string $questionName, ?string $questionAnswers, ?string $thankYouEmailTemplateId, array $options = []): array
    {
        $sourceType = $options['sourceType'] ?? $this->connectionConfig->getDefaultSourceType();
        $templateId = $thankYouEmailTemplateId;
        if ($templateId === null || $templateId === '') {
            $templateId = $this->connectionConfig->getThankYouEmailTemplateId();
        }

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
                'sourceType' => $sourceType,
            ],
            'sendSalesRepAssignmentNotification' => true,
        ];

        if (!empty($data['phone'])) {
            $payload['phones'] = [[
                'phone' => $data['phone'],
                'type' => 'Home',
                'primary' => true,
            ]];
        }

        if (!empty($data['address']) || !empty($data['city']) || !empty($data['state']) || !empty($data['postalCode'])) {
            $payload['addresses'] = [[
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'zipCode' => $data['postalCode'] ?? '',
                'country' => 'USA',
                'type' => 'Home',
                'primary' => true,
            ]];
        }

        if (!empty($data['comments'])) {
            $payload['notes'] = [[
                'note' => $data['comments'],
            ]];
        }

        if (!empty($templateId)) {
            $payload['thankYouEmailTemplateId'] = $templateId;
        }

        if (!empty($options['rotationId'])) {
            $payload['rotationId'] = $options['rotationId'];
        }

        if (!empty($options['websiteTracking'])) {
            $payload['websiteTracking'] = $options['websiteTracking'];
        } elseif (!empty($data['websiteTracking'])) {
            $payload['websiteTracking'] = [
                'domainAccountId' => $this->connectionConfig->getTrackingAccountId(),
                'guid' => $data['websiteTracking'],
            ];
        }

        $question = $this->buildQuestionPayload(
            $data['questionAnswerId'] ?? '',
            $questionId,
            $questionName,
            $questionAnswers
        );

        if ($question !== null) {
            $payload['questions'] = [$question];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildQuestionPayload(string $selectedAnswerId, ?string $questionId, ?string $questionName, ?string $questionAnswers): ?array
    {
        if ($selectedAnswerId === '') {
            return null;
        }

        if (!empty($questionId)) {
            return [
                'questionId' => $questionId,
                'answers' => [[
                    'answerId' => $selectedAnswerId,
                ]],
            ];
        }

        if (empty($questionName)) {
            return null;
        }

        $options = $this->questionAnswerParser->parse($questionAnswers);
        $selectedLabel = $options[$selectedAnswerId] ?? $selectedAnswerId;

        return [
            'name' => $questionName,
            'type' => 'checkbox',
            'answers' => [[
                'answer' => $selectedLabel,
            ]],
        ];
    }
}
