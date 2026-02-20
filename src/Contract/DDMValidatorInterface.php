<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Contract;

use JBSNewMedia\DDMBundle\Service\DDMField;

interface DDMValidatorInterface
{
    public function validate(mixed $value): bool;

    public function getErrorMessage(): ?string;

    public function setErrorMessage(?string $errorMessage): static;

    /** @return array<string, string> */
    public function getErrorMessageParameters(): array;

    /** @param array<string, string> $errorMessageParameters */
    public function setErrorMessageParameters(array $errorMessageParameters): static;

    public function getAlias(): ?string;

    public function setAlias(?string $alias): static;

    public function getPriority(): int;

    public function setPriority(int $priority): static;

    public function isRequired(): bool;

    public function setField(DDMField $field): static;

    public function getField(): ?DDMField;
}
