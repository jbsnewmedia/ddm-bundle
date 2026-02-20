<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;

use Doctrine\ORM\EntityManagerInterface;

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
    protected EntityManagerInterface $entityManager;
    protected mixed $entity = null;

    public function __construct(string $entityClass, string $context, iterable $fields, EntityManagerInterface $entityManager)
    {
        $this->entityClass = $entityClass;
        $this->context = $context;
        $this->fields = $fields;
        $this->entityManager = $entityManager;
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
            $field->init($this, $collectedFields);
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

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function setEntity(mixed $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getEntity(): mixed
    {
        return $this->entity;
    }

    public function getEntityId(): mixed
    {
        $entity = $this->entity;
        if ($entity && \is_object($entity) && method_exists($entity, 'getId')) {
            return $entity->getId();
        }
        return null;
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

    public function getContext(): string
    {
        return $this->context;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }
}
