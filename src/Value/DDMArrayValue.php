<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Value;

class DDMArrayValue extends DDMValue
{
    /**
     * @param array<mixed> $value
     */
    public function __construct(private array $value = [])
    {
    }

    /**
     * @return array<mixed>
     */
    public function getValue(): array
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        if (is_array($value)) {
            $this->value = $value;
        } elseif (null === $value) {
            $this->value = [];
        } else {
            $this->value = [$value];
        }
    }

    public function __toString(): string
    {
        return implode(', ', array_map(
            static fn (mixed $v): string => is_scalar($v) || null === $v ? (string) $v : '',
            $this->value
        ));
    }
}
