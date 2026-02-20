<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use Doctrine\ORM\EntityManagerInterface;

class DDMFactory
{
    /**
     * @param iterable<DDMField> $fields
     */
    public function __construct(
        private readonly iterable $fields,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function create(string $entityClass, string $context): DDM
    {
        return new DDM($entityClass, $context, $this->fields, $this->entityManager);
    }
}
