<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;

class DDM
{
    protected ?string $formTemplate = null;
    protected ?string $datatableTemplate = null;

    /** @param iterable<DDMField> $fields */
    public function __construct(protected string $entityClass, protected string $context, protected iterable $fields)
    {
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

                /** @var class-string $entityClass */
                $entityClass = $this->entityClass;
                $entityMatches = $ddmFieldAttribute->entity === $entityClass || ($ddmFieldAttribute->entity && strtolower((string) $ddmFieldAttribute->entity) === strtolower((new \ReflectionClass($entityClass))->getShortName()));
                $contextMatches = $ddmFieldAttribute->identifier === $this->context || $ddmFieldAttribute->entity === $this->context;

                if ($entityMatches || $contextMatches) {
                    $field->setOrder($ddmFieldAttribute->order);
                    $collectedFields[] = $field;
                    break;
                }
            }
        }

        usort($collectedFields, fn (DDMField $a, DDMField $b) => $a->getOrder() <=> $b->getOrder());

        foreach ($collectedFields as $field) {
            $field->init($collectedFields);
        }

        $this->fields = $collectedFields;
    }

    public function addField(DDMField $field): self
    {
        if (is_array($this->fields)) {
            $this->fields[] = $field;
        }

        return $this;
    }

    /** @return DDMField[] */
    public function getFields(): array
    {
        return is_array($this->fields) ? $this->fields : iterator_to_array($this->fields);
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
