<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Value;

class DDMArrayValue extends DDMValue
{
    /** @var array<mixed> */
    private array $value = [];

    /**
     * @param array<mixed> $value
     */
    public function __construct(array $value = [])
    {
        $this->value = $value;
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
        } elseif ($value === null) {
            $this->value = [];
        } else {
            $this->value = [$value];
        }
    }

    public function __toString(): string
    {
        return implode(', ', array_map('strval', $this->value));
    }
}
