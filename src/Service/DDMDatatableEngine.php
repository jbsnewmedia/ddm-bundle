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
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager
    ) {
    }

    public function handleRequest(Request $request, DDM $ddm, ?QueryBuilder $qb = null, ?string $translationDomain = null): JsonResponse
    {
        $fields = $ddm->getFields();
        $entityClass = $ddm->getEntityClass();
        $repository = $this->entityManager->getRepository($entityClass);
        $alias = 'p'; // Default alias, should probably be more dynamic or passed in

        if (null === $qb) {
            $qb = $repository->createQueryBuilder($alias);
        } else {
            $aliases = $qb->getRootAliases();
            if (count($aliases) > 0) {
                $alias = $aliases[0];
            }
        }

        $result = [];
        $result['head'] = [];
        $result['head']['columns'] = [];
        foreach ($fields as $field) {
            if (!$field->isRenderInTable()) {
                continue;
            }
            $column = [
                'name' => $this->translator->trans($field->getName(), [], $translationDomain),
                'sortable' => $field->isSortable(),
                'id' => $field->getIdentifier()
            ];
            if ($field->getIdentifier() === 'options') {
                $column['raw'] = true;
                $column['class'] = 'avalynx-datatable-options';
            }
            $result['head']['columns'][] = $column;
        }

        $params = $request->isMethod('POST') ? $request->request : $request->query;

        if ($params->has('sorting')) {
            $result['sorting'] = json_decode((string) $params->get('sorting'), true);
            if (null === $result['sorting'] || false === $result['sorting']) {
                $result['sorting'] = [];
            }
        } else {
            $result['sorting'] = [];
        }

        if ($params->has('search')) {
            $result['search']['value'] = $params->get('search');
        } else {
            $result['search'] = ['value' => ''];
        }

        $result['page'] = $params->getInt('page', 1);
        $result['perpage'] = $params->getInt('perpage', 10);
        $result['searchisnew'] = $params->getBoolean('searchisnew', false);

        if ($result['searchisnew']) {
            $result['page'] = 1;
        }

        // Search
        if ('' !== $result['search']['value']) {
            $orX = $qb->expr()->orX();
            foreach ($fields as $field) {
                if ($field->isLivesearch() && $field->getIdentifier() !== 'options') {
                    $orX->add($qb->expr()->like($alias . '.' . $field->getIdentifier(), ':search'));
                }
            }
            if ($orX->count() > 0) {
                $qb->andWhere($orX)
                    ->setParameter('search', '%' . $result['search']['value'] . '%');
            }
        }

        // Count filtered
        $countQb = clone $qb;
        $rootId = 'id'; // Default root id field
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);
        $identifier = $classMetadata->getIdentifierFieldNames();
        if (count($identifier) > 0) {
            $rootId = $identifier[0];
        }

        $totalFiltered = (int) $countQb->select('count(' . $alias . '.' . $rootId . ')')->getQuery()->getSingleScalarResult();
        $total = (int) $repository->createQueryBuilder($alias)->select('count(' . $alias . '.' . $rootId . ')')->getQuery()->getSingleScalarResult();

        // Sorting
        foreach ($result['sorting'] as $key => $sort) {
            $qb->addOrderBy($alias . '.' . $key, $sort);
        }

        // Pagination
        $maxPage = (int) ceil($totalFiltered / $result['perpage']);
        if ($maxPage < 1) {
            $maxPage = 1;
        }
        $result['page'] = (int) max(1, min($result['page'], $maxPage));

        $qb->setFirstResult(($result['page'] - 1) * $result['perpage'])
            ->setMaxResults($result['perpage']);

        $entities = $qb->getQuery()->getResult();

        $result['data'] = [];
        foreach ($entities as $entity) {
            $row = [];
            foreach ($fields as $field) {
                if (!$field->isRenderInTable()) {
                    continue;
                }
                $row[$field->getIdentifier()] = $field->render($entity);
            }
            $result['data'][] = ['data' => $row, 'config' => [], 'class' => '', 'data_class' => []];
        }

        $result['count'] = [
            'total' => $total,
            'filtered' => $totalFiltered,
            'start' => 1 + ($result['page'] - 1) * $result['perpage'],
            'end' => (int) min($totalFiltered, $result['page'] * $result['perpage']),
            'perpage' => $result['perpage'],
            'page' => $result['page'],
        ];

        return new JsonResponse($result);
    }
}
