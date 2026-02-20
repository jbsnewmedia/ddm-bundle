<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Contract;

use Doctrine\ORM\QueryBuilder;
use JBSNewMedia\DDMBundle\Service\DDM;

interface DDMFieldInterface
{
    public function getIdentifier(): string;

    public function setIdentifier(string $identifier): static;

    public function getName(): string;

    public function setName(string $name): static;

    public function getValueHandler(): DDMValueInterface;

    public function setValueHandler(DDMValueInterface $valueHandler): static;

    public function getValueForm(): mixed;

    public function setValueForm(mixed $value): static;

    public function getValueDatatable(object $entity): string;

    public function getOrder(): int;

    public function setOrder(int $order): static;

    public function isLivesearch(): bool;

    public function setLivesearch(bool $livesearch): static;

    public function isExtendsearch(): bool;

    public function setExtendsearch(bool $extendsearch): static;

    public function isSortable(): bool;

    public function setSortable(bool $sortable): static;

    public function isRenderInForm(): bool;

    public function setRenderInForm(bool $renderInForm): static;

    public function isRenderInTable(): bool;

    public function setRenderInTable(bool $renderInTable): static;

    public function isRenderSearch(): bool;

    public function setRenderSearch(bool $renderSearch): static;

    public function getTemplate(): string;

    public function setTemplate(string $template): static;

    public function addValidator(DDMValidatorInterface $validator): static;

    public function removeValidator(string $alias): static;

    /** @return DDMValidatorInterface[] */
    public function getValidators(): array;

    public function isRequired(): bool;

    public function validate(mixed $value): bool;

    public function getErrors(): array;

    public function getError(): ?array;

    public function getRoutes(): array;

    public function setRoutes(array $routes): static;

    public function getRoute(string $name): ?string;

    public function renderDatatable(object $entity): string;

    public function renderForm(object $entity): mixed;

    public function renderSearch(object $entity): mixed;

    /** @param iterable<DDMFieldInterface> $allFields */
    public function init(DDM $ddm, iterable $allFields): void;

    public function prepareValue(mixed $value): mixed;

    public function finalizeValue(mixed $value): mixed;

    public function getDdm(): ?DDM;

    public function getSearchExpression(QueryBuilder $qb, string $alias, string $search): ?object;

    /** @return DDMFieldInterface[] */
    public function getSubFields(): array;

    /** @param DDMFieldInterface[] $subFields */
    public function setSubFields(array $subFields): static;

    public function addSubField(DDMFieldInterface $subField): static;
}
