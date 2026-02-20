<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

use JBSNewMedia\DDMBundle\Contract\DDMValidatorInterface;
use JBSNewMedia\DDMBundle\Service\DDMField;

abstract class DDMValidator implements DDMValidatorInterface
{
    public const DEFAULT_PRIORITY = 100;

    protected ?string $errorMessage = null;
    /** @var array<string, string> */
    protected array $errorMessageParameters = [];
    protected ?string $alias = null;
    protected int $priority = self::DEFAULT_PRIORITY;
    protected ?DDMField $field = null;

    abstract public function validate(mixed $value): bool;

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /** @return array<string, string> */
    public function getErrorMessageParameters(): array
    {
        return $this->errorMessageParameters;
    }

    /** @param array<string, string> $errorMessageParameters */
    public function setErrorMessageParameters(array $errorMessageParameters): static
    {
        $this->errorMessageParameters = $errorMessageParameters;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function setField(DDMField $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function getField(): ?DDMField
    {
        return $this->field;
    }
}
