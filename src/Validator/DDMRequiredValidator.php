<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMRequiredValidator extends DDMValidator
{
    protected int $priority = self::DEFAULT_PRIORITY;

    public function validate(mixed $value): bool
    {
        if (null === $value) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('error.ddm.validator.required');
            }

            return false;
        }

        if (is_string($value) && '' === trim($value)) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('error.ddm.validator.required');
            }

            return false;
        }

        if (is_array($value) && 0 === count($value)) {
            if (null === $this->errorMessage) {
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
