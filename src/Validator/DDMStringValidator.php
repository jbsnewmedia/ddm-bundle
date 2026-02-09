<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMStringValidator extends DDMValidator
{
    protected int $priority = self::DEFAULT_PRIORITY;
    protected ?int $minLength = null;
    protected ?int $maxLength = null;

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
        $valueString = is_scalar($value) || (is_object($value) && method_exists($value, '__toString')) ? (string) $value : '';
        $length = mb_strlen($valueString);

        if (null !== $this->minLength && $length < $this->minLength) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('error.ddm.validator.string.min_length');
            }

            return false;
        }

        if (null !== $this->maxLength && $length > $this->maxLength) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('error.ddm.validator.string.max_length');
            }

            return false;
        }

        return true;
    }
}
