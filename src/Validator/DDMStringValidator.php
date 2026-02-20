<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMStringValidator extends DDMValidator
{
    protected ?int $minLength = null;
    protected ?int $maxLength = null;

    public function __construct()
    {
        $this->alias = 'string';
    }

    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    public function setMinLength(?int $minLength): self
    {
        $this->minLength = $minLength;

        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function setMaxLength(?int $maxLength): self
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function isRequired(): bool
    {
        return null !== $this->minLength && $this->minLength > 0;
    }

    public function validate(mixed $value): bool
    {
        $stringValue = is_scalar($value) || null === $value ? (string) $value : '';
        $length = mb_strlen($stringValue);

        if (null !== $this->minLength && $length < $this->minLength) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('string.min_length');
            }
            $this->errorMessageParameters = [
                '{min_length}' => (string) $this->minLength,
                '{current_length}' => (string) $length,
            ];

            return false;
        }

        if (null !== $this->maxLength && $length > $this->maxLength) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('string.max_length');
            }
            $this->errorMessageParameters = [
                '{max_length}' => (string) $this->maxLength,
                '{current_length}' => (string) $length,
            ];

            return false;
        }

        return true;
    }
}
