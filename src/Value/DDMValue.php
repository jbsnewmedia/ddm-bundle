<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Value;

use JBSNewMedia\DDMBundle\Contract\DDMValueInterface;

abstract class DDMValue implements DDMValueInterface
{
    protected string $type = 'text';

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    abstract public function getValue(): mixed;

    abstract public function setValue(mixed $value): void;

    abstract public function __toString(): string;
}
