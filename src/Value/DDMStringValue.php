<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Value;

class DDMStringValue extends DDMValue
{
    private ?string $value = null;

    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value === null ? null : (string) $value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
