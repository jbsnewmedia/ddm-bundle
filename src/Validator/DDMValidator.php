<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

use JBSNewMedia\DDMBundle\Service\DDMField;

abstract class DDMValidator
{
    public const DEFAULT_PRIORITY = 100;

    protected ?string $errorMessage = null;
    protected array $errorMessageParameters = [];
    protected ?string $alias = null;
    protected int $priority = self::DEFAULT_PRIORITY;
    protected ?DDMField $field = null;

    abstract public function validate(mixed $value): bool;

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getErrorMessageParameters(): array
    {
        return $this->errorMessageParameters;
    }

    public function setErrorMessageParameters(array $errorMessageParameters): self
    {
        $this->errorMessageParameters = $errorMessageParameters;
        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function setField(DDMField $field): self
    {
        $this->field = $field;
        return $this;
    }

    public function getField(): ?DDMField
    {
        return $this->field;
    }
}
