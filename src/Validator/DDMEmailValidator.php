<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMEmailValidator extends DDMValidator
{
    public function __construct()
    {
        $this->alias = 'email';
    }

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true;
        }

        $stringValue = is_scalar($value) ? (string) $value : '';
        if (!filter_var($stringValue, FILTER_VALIDATE_EMAIL)) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('email.invalid');
            }

            return false;
        }

        return true;
    }

    public function isRequired(): bool
    {
        return false;
    }
}
