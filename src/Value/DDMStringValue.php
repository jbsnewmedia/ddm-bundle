<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Value;

class DDMStringValue extends DDMValue
{
    public function __construct(private ?string $value = null)
    {
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = null === $value ? null : (is_scalar($value) ? (string) $value : '');
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
