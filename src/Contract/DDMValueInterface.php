<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Contract;

interface DDMValueInterface extends \Stringable
{
    public function getType(): string;

    public function setType(string $type): static;

    public function getValue(): mixed;

    public function setValue(mixed $value): void;
}
