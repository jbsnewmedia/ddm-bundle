<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMRequiredValidator extends DDMValidator
{
    protected int $priority = self::DEFAULT_PRIORITY;

    public function validate(mixed $value): bool
    {
        if ($value === null) {
            if ($this->errorMessage === null) {
                $this->setErrorMessage('error.ddm.validator.required');
            }
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            if ($this->errorMessage === null) {
                $this->setErrorMessage('error.ddm.validator.required');
            }
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            if ($this->errorMessage === null) {
                $this->setErrorMessage('error.ddm.validator.required');
            }
            return false;
        }

        return true;
    }

    public function isRequired(): bool
    {
        return true;
    }
}
