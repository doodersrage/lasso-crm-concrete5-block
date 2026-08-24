<?php

namespace Concrete\Package\LassoCrm\Lasso;

use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Validator\String\EmailValidator;

class SubmissionValidator
{
    /** @var EmailValidator */
    private $emailValidator;

    public function __construct(EmailValidator $emailValidator)
    {
        $this->emailValidator = $emailValidator;
    }

    /**
     * @param array<string, string> $data
     */
    public function validate(array $data): ErrorList
    {
        $errors = new ErrorList();

        if ($data['firstName'] === '' || $data['lastName'] === '') {
            $errors->add(t('First name and last name are required.'));
        }

        if ($data['email'] === '') {
            $errors->add(t('Email address is required.'));
        } elseif (!$this->emailValidator->isValid($data['email'], $errors)) {
            // EmailValidator adds its own message when validation fails.
        }

        if ($data['postalCode'] === '') {
            $errors->add(t('Zip code is required.'));
        }

        return $errors;
    }
}
