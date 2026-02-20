<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use Doctrine\ORM\QueryBuilder;
use JBSNewMedia\DDMBundle\Contract\DDMFieldInterface;
use JBSNewMedia\DDMBundle\Contract\DDMValidatorInterface;
use JBSNewMedia\DDMBundle\Contract\DDMValueInterface;
use JBSNewMedia\DDMBundle\Trait\DDMEntityAccessor;
use JBSNewMedia\DDMBundle\Value\DDMStringValue;

abstract class DDMField implements DDMFieldInterface
{
    use DDMEntityAccessor;

    public const DEFAULT_PRIORITY = 100;

    /**
     * Reserved identifier for the "options" column (action buttons column).
     * Use this constant instead of the string literal 'options'.
     */
    public const IDENTIFIER_OPTIONS = 'options';

    protected string $identifier = '';
    protected string $name = '';
    protected ?DDMValueInterface $valueHandler = null;
    protected int $order = 100;
    protected bool $livesearch = true;
    protected bool $extendsearch = true;
    protected bool $sortable = true;
    protected bool $renderInForm = true;
    protected bool $renderInTable = true;
    protected bool $renderSearch = true;
    protected string $template = '@DDM/fields/text.html.twig';
    /** @var DDMValidatorInterface[] */
    protected array $validators = [];
    /** @var array<int, array{message: string, parameters: array<string, string>, domain: string}> */
    protected array $errors = [];
    /** @var DDMFieldInterface[] */
    protected array $subFields = [];
    /** @var array<string, string> */
    protected array $routes = [];
    protected ?DDM $ddm = null;

    /** Static counter for unique query parameter names (replaces uniqid()). */
    private static int $paramCounter = 0;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    /** @return DDMFieldInterface[] */
    public function getSubFields(): array
    {
        return $this->subFields;
    }

    /** @param DDMFieldInterface[] $subFields */
    public function setSubFields(array $subFields): static
    {
        $this->subFields = $subFields;

        return $this;
    }

    public function addSubField(DDMFieldInterface $subField): static
    {
        $this->subFields[] = $subField;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getValueHandler(): DDMValueInterface
    {
        if (null === $this->valueHandler) {
            $this->valueHandler = new DDMStringValue();
        }

        return $this->valueHandler;
    }

    public function setValueHandler(DDMValueInterface $valueHandler): static
    {
        $this->valueHandler = $valueHandler;

        return $this;
    }

    /**
     * Returns the raw value for the form.
     */
    public function getValueForm(): mixed
    {
        return $this->getValueHandler()->getValue();
    }

    /**
     * Sets the value via the ValueHandler (for forms).
     */
    public function setValueForm(mixed $value): static
    {
        $this->getValueHandler()->setValue($value);

        return $this;
    }

    /**
     * @deprecated use getValueForm() instead
     */
    public function getValue(): mixed
    {
        return $this->getValueForm();
    }

    /**
     * @deprecated use setValueForm() instead
     */
    public function setValue(mixed $value): static
    {
        return $this->setValueForm($value);
    }

    /**
     * Returns the stringified value for the datatable, read from the entity via getter convention.
     */
    public function getValueDatatable(object $entity): string
    {
        $rawResult = $this->getEntityValue($entity, $this->identifier);
        if (null !== $rawResult) {
            $this->getValueHandler()->setValue($this->prepareValue($rawResult));
        }

        return (string) $this->getValueHandler();
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function isLivesearch(): bool
    {
        return $this->livesearch;
    }

    public function setLivesearch(bool $livesearch): static
    {
        $this->livesearch = $livesearch;

        return $this;
    }

    public function isExtendsearch(): bool
    {
        return $this->extendsearch;
    }

    public function setExtendsearch(bool $extendsearch): static
    {
        $this->extendsearch = $extendsearch;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $sortable): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function isRenderInForm(): bool
    {
        return $this->renderInForm;
    }

    public function setRenderInForm(bool $renderInForm): static
    {
        $this->renderInForm = $renderInForm;

        return $this;
    }

    public function isRenderInTable(): bool
    {
        return $this->renderInTable;
    }

    public function setRenderInTable(bool $renderInTable): static
    {
        $this->renderInTable = $renderInTable;

        return $this;
    }

    public function isRenderSearch(): bool
    {
        return $this->renderSearch;
    }

    public function setRenderSearch(bool $renderSearch): static
    {
        $this->renderSearch = $renderSearch;

        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function addValidator(DDMValidatorInterface $validator): static
    {
        if (null !== $validator->getAlias()) {
            $this->removeValidator($validator->getAlias());
        }
        $validator->setField($this);
        $this->validators[] = $validator;
        usort($this->validators, static fn (DDMValidatorInterface $a, DDMValidatorInterface $b): int => $b->getPriority() <=> $a->getPriority());

        return $this;
    }

    public function removeValidator(string $alias): static
    {
        $this->validators = array_values(array_filter(
            $this->validators,
            static fn (DDMValidatorInterface $v): bool => $v->getAlias() !== $alias
        ));

        return $this;
    }

    /** @return DDMValidatorInterface[] */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function isRequired(): bool
    {
        foreach ($this->validators as $validator) {
            if ($validator->isRequired()) {
                return true;
            }
        }

        return false;
    }

    public function validate(mixed $value): bool
    {
        $this->errors = [];
        foreach ($this->validators as $validator) {
            if (!$validator->validate($value)) {
                $domain = 'ddm_validator_'.($validator->getAlias() ?? 'default');
                $this->errors[] = [
                    'message' => (string) $validator->getErrorMessage(),
                    'parameters' => $validator->getErrorMessageParameters(),
                    'domain' => $domain,
                ];

                // Fail-fast: stop at first error
                return false;
            }
        }

        return true;
    }

    /** @return array<int, array{message: string, parameters: array<string, string>, domain: string}> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return array{message: string, parameters: array<string, string>, domain: string}|null */
    public function getError(): ?array
    {
        return $this->errors[0] ?? null;
    }

    /** @return array<string, string> */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /** @param array<string, string> $routes */
    public function setRoutes(array $routes): static
    {
        $this->routes = $routes;

        return $this;
    }

    public function getRoute(string $name): ?string
    {
        return $this->routes[$name] ?? null;
    }

    public function renderDatatable(object $entity): string
    {
        return $this->getValueDatatable($entity);
    }

    public function renderForm(object $entity): mixed
    {
        return $this->prepareValue($this->getEntityValue($entity, $this->identifier));
    }

    public function renderSearch(object $entity): mixed
    {
        return $this->renderDatatable($entity);
    }

    /**
     * @param iterable<DDMFieldInterface> $allFields
     */
    public function init(DDM $ddm, iterable $allFields): void
    {
        $this->ddm = $ddm;
    }

    public function prepareValue(mixed $value): mixed
    {
        return $value;
    }

    public function finalizeValue(mixed $value): mixed
    {
        return $value;
    }

    public function getDdm(): ?DDM
    {
        return $this->ddm;
    }

    public function getSearchExpression(QueryBuilder $qb, string $alias, string $search): ?object
    {
        if (!$this->isLivesearch() || self::IDENTIFIER_OPTIONS === $this->getIdentifier()) {
            return null;
        }

        $paramName = 'search_'.str_replace('.', '_', $this->getIdentifier()).'_'.(++self::$paramCounter);
        $qb->setParameter($paramName, '%'.$search.'%');

        return $qb->expr()->like($alias.'.'.$this->getIdentifier(), ':'.$paramName);
    }
}
