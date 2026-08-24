<?php

namespace Concrete\Package\LassoCrm\Lasso;

class RegistrantPayloadBuilder
{
    /** @var QuestionAnswerParser */
    private $questionAnswerParser;

    public function __construct(QuestionAnswerParser $questionAnswerParser)
    {
        $this->questionAnswerParser = $questionAnswerParser;
    }

    /**
     * @param array<string, string> $data
     *
     * @return array<string, mixed>
     */
    public function build(array $data, ?string $questionId, ?string $questionName, ?string $questionAnswers, ?string $thankYouEmailTemplateId): array
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

        if (!empty($thankYouEmailTemplateId)) {
            $payload['thankYouEmailTemplateId'] = $thankYouEmailTemplateId;
        }

        $question = $this->buildQuestionPayload(
            $data['questionAnswerId'],
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
