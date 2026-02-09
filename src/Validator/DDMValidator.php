<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

abstract class DDMValidator
{
    public const DEFAULT_PRIORITY = 100;

    protected ?string $errorMessage = null;
    protected ?string $alias = null;
    protected int $priority = self::DEFAULT_PRIORITY;

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
}
