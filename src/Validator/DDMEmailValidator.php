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

        if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
            if ($this->errorMessage === null) {
                $this->setErrorMessage('error.ddm.validator.email.invalid');
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
