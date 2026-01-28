<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

class DDMFactory
{
    /**
     * @param iterable<DDMField> $fields
     */
    public function __construct(private readonly iterable $fields)
    {
    }

    public function create(string $entityClass, string $context): DDM
    {
        return new DDM($entityClass, $context, $this->fields);
    }
}
