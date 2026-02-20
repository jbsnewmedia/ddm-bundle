<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

use JBSNewMedia\DDMBundle\Service\DDMField;

class DDMUniqueValidator extends DDMValidator
{
    public function __construct()
    {
        $this->alias = 'unique';
    }

    protected int $priority = self::DEFAULT_PRIORITY;

    public function validate(mixed $value): bool
    {
        if (empty($value)) {
            return true;
        }

        $field = $this->getField();
        if (!$field instanceof DDMField) {
            return true;
        }
        $ddm = $field->getDdm();
        if ($ddm === null) {
            return true;
        }

        $entityManager = $ddm->getEntityManager();
        $entityClass = $ddm->getEntityClass();
        $fieldIdentifier = $field->getIdentifier();
        $entityId = $ddm->getEntityId();

        if (!$entityManager || !$entityClass || !$fieldIdentifier) {
            return true;
        }

        $repository = $entityManager->getRepository($entityClass);
        $qb = $repository->createQueryBuilder('e')
            ->select('count(e)')
            ->where('e.' . $fieldIdentifier . ' = :value')
            ->setParameter('value', $value);

        if ($entityId) {
            $qb->andWhere('e.id != :id')
                ->setParameter('id', $entityId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        if ($count > 0) {
            if ($this->errorMessage === null) {
                $this->setErrorMessage('unique');
            }
            return false;
        }

        return true;
    }
}
