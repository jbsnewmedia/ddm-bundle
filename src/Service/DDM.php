<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;

class DDM
{
    protected string $entityClass;
    protected string $context;
    protected ?string $formTemplate = null;
    protected ?string $datatableTemplate = null;
    protected ?string $title = null;
    /** @var DDMField[] */
    protected iterable $fields = [];
    protected array $routes = [];

    public function __construct(string $entityClass, string $context, iterable $fields)
    {
        $this->entityClass = $entityClass;
        $this->context = $context;
        $this->fields = $fields;
        $this->loadFields();
    }

    public function getFormTemplate(): ?string
    {
        return $this->formTemplate;
    }

    public function setFormTemplate(?string $formTemplate): self
    {
        $this->formTemplate = $formTemplate;
        return $this;
    }

    public function getDatatableTemplate(): ?string
    {
        return $this->datatableTemplate;
    }

    public function setDatatableTemplate(?string $datatableTemplate): self
    {
        $this->datatableTemplate = $datatableTemplate;
        return $this;
    }

    public function setTemplate(?string $template): self
    {
        $this->formTemplate = $template;
        $this->datatableTemplate = $template;
        return $this;
    }

    protected function loadFields(): void
    {
        $collectedFields = [];
        foreach ($this->fields as $field) {
            $reflectionClass = new \ReflectionClass($field);
            $attributes = $reflectionClass->getAttributes(DDMFieldAttribute::class);
            foreach ($attributes as $attribute) {
                /** @var DDMFieldAttribute $ddmFieldAttribute */
                $ddmFieldAttribute = $attribute->newInstance();
                $entityMatches = $ddmFieldAttribute->entity === $this->entityClass || ($ddmFieldAttribute->entity && strtolower($ddmFieldAttribute->entity) === strtolower((new \ReflectionClass($this->entityClass))->getShortName()));
                $contextMatches = $ddmFieldAttribute->identifier === $this->context || $ddmFieldAttribute->entity === $this->context;

                if ($entityMatches || $contextMatches) {
                    $field->setOrder($ddmFieldAttribute->order);
                    $collectedFields[] = $field;
                    break;
                }
            }
        }

        usort($collectedFields, function (DDMField $a, DDMField $b) {
            return $a->getOrder() <=> $b->getOrder();
        });

        foreach ($collectedFields as $field) {
            $field->init($collectedFields);
        }

        $this->fields = $collectedFields;
    }

    public function addField(DDMField $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function setRoutes(array $routes): self
    {
        $this->routes = $routes;
        foreach ($this->fields as $field) {
            $field->setRoutes($routes);
        }
        return $this;
    }

    public function getRoute(string $name): ?string
    {
        return $this->routes[$name] ?? null;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }
}
