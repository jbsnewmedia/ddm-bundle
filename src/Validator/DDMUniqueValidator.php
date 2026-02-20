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
        if (null === $ddm) {
            return true;
        }

        $entityManager = $ddm->getEntityManager();
        /** @var class-string<object> $entityClass */
        $entityClass = $ddm->getEntityClass();
        $fieldIdentifier = $field->getIdentifier();

        if ('' === $fieldIdentifier) {
            return true;
        }

        $entityId = $ddm->getEntityId();

        $repository = $entityManager->getRepository($entityClass);
        $qb = $repository->createQueryBuilder('e')
            ->select('count(e)')
            ->where('e.'.$fieldIdentifier.' = :value')
            ->setParameter('value', $value);

        if (null !== $entityId) {
            $qb->andWhere('e.id != :id')
                ->setParameter('id', $entityId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        if ($count > 0) {
            if (null === $this->errorMessage) {
                $this->setErrorMessage('unique');
            }

            return false;
        }

        return true;
    }
}
