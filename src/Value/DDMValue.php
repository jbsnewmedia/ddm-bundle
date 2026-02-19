<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Value;

abstract class DDMValue
{
    protected string $type = 'text';

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    abstract public function getValue(): mixed;
    abstract public function setValue(mixed $value): void;
    abstract public function __toString(): string;
}
