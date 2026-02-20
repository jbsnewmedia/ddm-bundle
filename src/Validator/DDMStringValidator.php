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
        return $this->minLength !== null && $this->minLength > 0;
    }

    public function validate(mixed $value): bool
    {
        $length = mb_strlen((string) $value);

        if ($this->minLength !== null && $length < $this->minLength) {
            if ($this->errorMessage === null) {
                $this->setErrorMessage('string.min_length');
            }
            $this->errorMessageParameters = [
                '{min_length}' => (string) $this->minLength,
                '{current_length}' => (string) $length,
            ];
            return false;
        }

        if ($this->maxLength !== null && $length > $this->maxLength) {
            if ($this->errorMessage === null) {
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
