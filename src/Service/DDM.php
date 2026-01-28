<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;

class DDM
{
    protected string $entityClass;
    protected string $context;
    /** @var DDMField[] */
    protected iterable $fields = [];

    public function __construct(string $entityClass, string $context, iterable $fields)
    {
        $this->entityClass = $entityClass;
        $this->context = $context;
        $this->fields = $fields;
        $this->loadFields();
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
}
