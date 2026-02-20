<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class DDMDatatableEngine
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handleRequest(
        Request $request,
        DDM $ddm,
        ?QueryBuilder $qb = null,
        ?string $translationDomain = null,
    ): JsonResponse {
        $fields = $ddm->getFields();
        /** @var class-string<object> $entityClass */
        $entityClass = $ddm->getEntityClass();
        $repository = $this->entityManager->getRepository($entityClass);
        $alias = 'p';

        if (null === $qb) {
            $qb = $repository->createQueryBuilder($alias);
        } else {
            $aliases = $qb->getRootAliases();
            if (count($aliases) > 0) {
                $alias = $aliases[0];
            }
        }

        $headColumns = [];
        foreach ($fields as $field) {
            if (!$field->isRenderInTable()) {
                continue;
            }
            $column = [
                'name' => $this->translator->trans($field->getName(), [], $translationDomain),
                'sortable' => $field->isSortable(),
                'id' => $field->getIdentifier(),
            ];
            if (DDMField::IDENTIFIER_OPTIONS === $field->getIdentifier()) {
                $column['raw'] = true;
                $column['class'] = 'avalynx-datatable-options';
            }
            $headColumns[] = $column;
        }

        $params = $request->isMethod('POST') ? $request->request : $request->query;

        $sortingRaw = json_decode((string) $params->get('sorting', '[]'), true);
        /** @var array<string, string> $sorting */
        $sorting = is_array($sortingRaw) ? $sortingRaw : [];

        $search = $params->has('search') ? (string) $params->get('search') : '';
        $page = $params->getInt('page', 1);
        $perpage = $params->getInt('perpage', 10);
        $searchIsNew = $params->getBoolean('searchisnew', false);

        /** @var array<string, mixed> $searchFields */
        $searchFields = $params->all()['search_fields'] ?? [];

        // Remove empty values from search_fields
        foreach ($searchFields as $key => $value) {
            if (null === $value || '' === $value || (is_array($value) && [] === $value)) {
                unset($searchFields[$key]);
            }
        }

        if ($searchIsNew) {
            $page = 1;
        }

        // Global search
        if ('' !== $search) {
            $orX = $qb->expr()->orX();
            foreach ($fields as $field) {
                if (DDMField::IDENTIFIER_OPTIONS === $field->getIdentifier()) {
                    continue;
                }
                $searchExpression = $field->getSearchExpression($qb, $alias, $search);
                if (null !== $searchExpression) {
                    $orX->add($searchExpression);
                }
            }
            if ($orX->count() > 0) {
                $qb->andWhere($orX);
            }
        }

        // Extended search (per-field)
        if ([] !== $searchFields) {
            foreach ($searchFields as $fieldIdentifier => $searchValue) {
                if (null === $searchValue || '' === $searchValue) {
                    continue;
                }
                foreach ($fields as $field) {
                    if ($field->getIdentifier() === $fieldIdentifier && $field->isExtendsearch()) {
                        $expressionValue = is_array($searchValue)
                            ? implode(',', array_map(
                                static fn (mixed $v): string => is_scalar($v) || null === $v ? (string) $v : '',
                                $searchValue
                            ))
                            : (is_scalar($searchValue) ? (string) $searchValue : '');
                        $searchExpression = $field->getSearchExpression($qb, $alias, $expressionValue);
                        if (null !== $searchExpression) {
                            $qb->andWhere($searchExpression);
                        }
                    }
                }
            }
        }

        // Count total (unfiltered) and filtered
        $countQb = clone $qb;
        /** @var class-string<object> $entityClass */
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);
        $identifier = $classMetadata->getIdentifierFieldNames();
        $rootId = count($identifier) > 0 ? $identifier[0] : 'id';

        $totalFiltered = (int) $countQb
            ->select('count('.$alias.'.'.$rootId.')')
            ->getQuery()
            ->getSingleScalarResult();

        $total = (int) $repository
            ->createQueryBuilder($alias)
            ->select('count('.$alias.'.'.$rootId.')')
            ->getQuery()
            ->getSingleScalarResult();

        // Sorting
        foreach ($sorting as $key => $sort) {
            $qb->addOrderBy($alias.'.'.$key, $sort);
        }

        // Pagination
        $maxPage = max(1, (int) ceil($totalFiltered / $perpage));
        $page = (int) max(1, min($page, $maxPage));

        $qb->setFirstResult(($page - 1) * $perpage)
            ->setMaxResults($perpage);

        /** @var list<object> $entities */
        $entities = $qb->getQuery()->getResult();

        $data = [];
        foreach ($entities as $entity) {
            $row = [];
            foreach ($fields as $field) {
                if (!$field->isRenderInTable()) {
                    continue;
                }
                $row[$field->getIdentifier()] = $field->renderDatatable($entity);
            }
            $data[] = ['data' => $row, 'config' => [], 'class' => '', 'data_class' => []];
        }

        return new JsonResponse([
            'head' => ['columns' => $headColumns],
            'sorting' => $sorting,
            'search' => ['value' => $search],
            'page' => $page,
            'perpage' => $perpage,
            'searchisnew' => $searchIsNew,
            'search_fields' => $searchFields,
            'data' => $data,
            'count' => [
                'total' => $total,
                'filtered' => $totalFiltered,
                'start' => $totalFiltered > 0 ? 1 + ($page - 1) * $perpage : 0,
                'end' => (int) min($totalFiltered, $page * $perpage),
                'perpage' => $perpage,
                'page' => $page,
            ],
        ]);
    }
}
