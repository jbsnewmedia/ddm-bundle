<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use JBSNewMedia\DDMBundle\Validator\DDMValidator;
use JBSNewMedia\DDMBundle\Value\DDMStringValue;
use Doctrine\ORM\QueryBuilder;
use JBSNewMedia\DDMBundle\Value\DDMValue;

abstract class DDMField
{
    public const DEFAULT_PRIORITY = 100;

    protected string $identifier = '';
    protected string $name = '';
    protected ?DDMValue $valueHandler = null;
    protected int $order = 100;
    protected bool $livesearch = true;
    protected bool $extendsearch = true;
    protected bool $sortable = true;
    protected bool $renderInForm = true;
    protected bool $renderInTable = true;
    protected bool $renderSearch = true;
    protected string $template = '@DDM/fields/text.html.twig';
    /** @var DDMValidator[] */
    protected array $validators = [];
    protected array $errors = [];
    /** @var DDMField[] */
    protected array $subFields = [];
    protected array $routes = [];
    protected ?DDM $ddm = null;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /** @return DDMField[] */
    public function getSubFields(): array
    {
        return $this->subFields;
    }

    /** @param DDMField[] $subFields */
    public function setSubFields(array $subFields): self
    {
        $this->subFields = $subFields;
        return $this;
    }

    public function addSubField(DDMField $subField): self
    {
        $this->subFields[] = $subField;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getValueHandler(): DDMValue
    {
        if ($this->valueHandler === null) {
            $this->valueHandler = new DDMStringValue();
        }
        return $this->valueHandler;
    }

    public function setValueHandler(DDMValue $valueHandler): self
    {
        $this->valueHandler = $valueHandler;
        return $this;
    }

    /**
     * Gibt den rohen Wert für das Formular zurück.
     */
    public function getValueForm(): mixed
    {
        return $this->getValueHandler()->getValue();
    }

    /**
     * Setzt den Wert über den ValueHandler (für das Formular).
     */
    public function setValueForm(mixed $value): self
    {
        $this->getValueHandler()->setValue($value);
        return $this;
    }

    // BC: Alias für Twig/Templates, die noch `field.value`/`getValue()` verwenden
    public function getValue(): mixed
    {
        return $this->getValueForm();
    }

    // BC: Alias für alte Aufrufer
    public function setValue(mixed $value): self
    {
        return $this->setValueForm($value);
    }

    /**
     * Gibt den Wert für die Datatable zurück.
     */
    public function getValueDatatable(object $entity): string
    {
        $method = 'get' . ucfirst($this->identifier);
        if (method_exists($entity, $method)) {
            $rawResult = $entity->$method();
            $prepared = $this->prepareValue($rawResult);
            $this->getValueHandler()->setValue($prepared);
        }

        return (string) $this->getValueHandler();
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function isLivesearch(): bool
    {
        return $this->livesearch;
    }

    public function setLivesearch(bool $livesearch): self
    {
        $this->livesearch = $livesearch;
        return $this;
    }

    public function isExtendsearch(): bool
    {
        return $this->extendsearch;
    }

    public function setExtendsearch(bool $extendsearch): self
    {
        $this->extendsearch = $extendsearch;
        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $sortable): self
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function isRenderInForm(): bool
    {
        return $this->renderInForm;
    }

    public function setRenderInForm(bool $renderInForm): self
    {
        $this->renderInForm = $renderInForm;
        return $this;
    }

    public function isRenderInTable(): bool
    {
        return $this->renderInTable;
    }

    public function setRenderInTable(bool $renderInTable): self
    {
        $this->renderInTable = $renderInTable;
        return $this;
    }

    public function isRenderSearch(): bool
    {
        return $this->renderSearch;
    }

    public function setRenderSearch(bool $renderSearch): self
    {
        $this->renderSearch = $renderSearch;
        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function addValidator(DDMValidator $validator): self
    {
        if ($validator->getAlias()) {
            $this->removeValidator($validator->getAlias());
        }
        $validator->setField($this);
        $this->validators[] = $validator;
        usort($this->validators, function (DDMValidator $a, DDMValidator $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
        return $this;
    }

    public function removeValidator(string $alias): self
    {
        $this->validators = array_filter($this->validators, function (DDMValidator $validator) use ($alias) {
            return $validator->getAlias() !== $alias;
        });
        return $this;
    }

    /** @return DDMValidator[] */
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
                $domain = 'ddm_validator_' . ($validator->getAlias() ?? 'default');
                $this->errors[] = [
                    'message' => $validator->getErrorMessage(),
                    'parameters' => $validator->getErrorMessageParameters(),
                    'domain' => $domain
                ];
                return false;
            }
        }
        return true;
    }


    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getError(): ?array
    {
        return $this->errors[0] ?? null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function setRoutes(array $routes): self
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
        $method = 'get' . ucfirst($this->identifier);
        if (method_exists($entity, $method)) {
            return $this->prepareValue($entity->$method());
        }
        return null;
    }

    public function renderSearch(object $entity): mixed
    {
        return $this->renderDatatable($entity);
    }

    /**
     * @param iterable<DDMField> $allFields
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
        if (!$this->isLivesearch() || $this->getIdentifier() === 'options') {
            return null;
        }

        $paramName = 'search_' . str_replace('.', '_', $this->getIdentifier() . '_' . uniqid());
        $qb->setParameter($paramName, '%' . $search . '%');

        return $qb->expr()->like($alias . '.' . $this->getIdentifier(), ':' . $paramName);
    }
}
