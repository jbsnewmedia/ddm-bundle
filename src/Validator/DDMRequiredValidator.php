<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMRequiredValidator extends DDMValidator
{
    public function __construct()
    {
        $this->alias = 'required';
    }

    public function validate(mixed $value): bool
    {
        if (null === $value) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('required');
            }

            return false;
        }

        if (is_string($value) && '' === trim($value)) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('required');
            }

            return false;
        }

        if (is_array($value)) {
            if (0 === count($value)) {
                if (null === $this->errorMessage) {
                    $this->setErrorMessage('required');
                }

                return false;
            }

            // Check if all array elements are empty strings
            $allEmpty = true;
            foreach ($value as $val) {
                if (null !== $val && (!is_string($val) || '' !== trim($val))) {
                    $allEmpty = false;
                    break;
                }
            }

            if ($allEmpty) {
                if (null === $this->errorMessage) {
                    $this->setErrorMessage('required');
                }

                return false;
            }
        }

        return true;
    }

    public function isRequired(): bool
    {
        return true;
    }
}
