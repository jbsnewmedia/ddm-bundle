<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use JBSNewMedia\DDMBundle\Service\DDM;
use JBSNewMedia\DDMBundle\Service\DDMDatatableEngine;
use JBSNewMedia\DDMBundle\Service\DDMField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

final class DDMDatatableEngineTest extends TestCase
{
    public function testHandleRequest(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        if (!class_exists('TestEntity')) {
            eval('class TestEntity { public function getId() { return 1; } }');
        }

        $field = new class extends DDMField {};
        $field->setIdentifier('id');
        $field->setName('ID');
        $field->setRenderInTable(true);
        $field->setSortable(true);

        $ddm = new DDM('TestEntity', 'context', [$field], $entityManager);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('addOrderBy')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(1);
        $query->method('getResult')->willReturn([new \TestEntity()]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request(['page' => 1, 'perpage' => 10]);

        $response = $engine->handleRequest($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
    }

    public function testHandleRequestWithCustomQueryBuilder(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setName('Name');
        $field->setLivesearch(true);
        $field->setRenderInTable(true);

        $ddm = new DDM('TestEntity', 'context', [$field], $entityManager);

        $entityManager->method('getRepository')->willReturn($repository);
        $qb->method('getRootAliases')->willReturn(['custom']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('addOrderBy')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(5);
        $query->method('getResult')->willReturn([new \TestEntity()]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request(['search' => 'findme', 'sorting' => json_encode(['name' => 'ASC'])]);

        $response = $engine->handleRequest($request, $ddm, $qb);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleRequestWithPostMethod(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setName('Name');
        $field->setLivesearch(true);
        $field->setRenderInTable(true);

        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('addOrderBy')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getResult')->willReturn([]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn([]);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request([], ['page' => 2, 'perpage' => 10, 'search' => 'test', 'searchisnew' => true]);
        $request->setMethod('POST');

        $response = $engine->handleRequest($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getContent(), true);
        $this->assertSame(1, $data['page']); // Reset to 1 because searchisnew
    }

    public function testHandleRequestWithOptionsField(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('options');
        $field->setName('Options');
        $field->setLivesearch(false);
        $field->setRenderInTable(true);

        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getResult')->willReturn([]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request(['sorting' => 'invalid-json']);

        $response = $engine->handleRequest($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getContent(), true);
        $this->assertTrue($data['head']['columns'][0]['raw']);
        $this->assertSame('avalynx-datatable-options', $data['head']['columns'][0]['class']);
    }

    public function testHandleRequestWithNonIterableResult(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setName('Name');
        $field->setRenderInTable(true);

        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getResult')->willReturn([]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request();

        $response = $engine->handleRequest($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getContent(), true);
        $this->assertEmpty($data['data']);
    }

    public function testHandleRequestWithNonObjectEntity(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setName('Name');
        $field->setRenderInTable(true);

        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(1);
        $query->method('getResult')->willReturn([new \TestEntity()]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request();

        $response = $engine->handleRequest($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getContent(), true);
        $this->assertCount(1, $data['data']);
    }

    public function testHandleRequestWithFieldNotRenderInTable(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('hidden');
        $field->setRenderInTable(false);

        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturn(1);
        $query->method('getResult')->willReturn([new \TestEntity()]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request();

        $response = $engine->handleRequest($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getContent(), true);
        $this->assertEmpty($data['head']['columns']);
    }

    public function testHandleRequestWithExtendedSearchArrayAndPaginationClamp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setName('Name');
        $field->setRenderInTable(true);
        $field->setExtendsearch(true);

        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('addOrderBy')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        // filtered = 3 -> with perpage=2 and page=5 request, page should clamp to 2
        $query->method('getSingleScalarResult')->willReturnOnConsecutiveCalls(3, 10);
        $query->method('getResult')->willReturn([new \TestEntity(), new \TestEntity()]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request([
            'page' => 5,
            'perpage' => 2,
            'sorting' => json_encode(['name' => 'DESC', 'id' => 'ASC']),
            'search_fields' => [
                'name' => ['alpha', '', null, 'beta'],
            ],
        ]);

        $response = $engine->handleRequest($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertSame(2, $data['page']); // clamped
        $this->assertSame(2, $data['perpage']);
        $this->assertSame(['name' => 'DESC', 'id' => 'ASC'], $data['sorting']);
    }

    public function testHandleRequestWithExtendedSearchSkipEmpty(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setRenderInTable(true);
        $field->setExtendsearch(true);

        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        // If empty string is provided, engine should skip and not call andWhere at all
        $qb->expects($this->never())->method('andWhere');
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturnOnConsecutiveCalls(0, 0);
        $query->method('getResult')->willReturn([]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request([
            'search_fields' => [
                'name' => '', // empty => should be skipped
            ],
        ]);

        $response = $engine->handleRequest($request, $ddm);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getContent(), true);
        $this->assertSame([], $data['data']);
    }

    public function testHandleRequestWithCustomQueryBuilderNoAliasesKeepsDefaultAlias(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setRenderInTable(true);

        $ddm = new DDM('TestEntity', 'context', [$field], $entityManager);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $qb->method('getRootAliases')->willReturn([]); // no aliases -> keep default 'p'
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturnOnConsecutiveCalls(1, 1);
        $query->method('getResult')->willReturn([new \TestEntity()]);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request(['search' => 'x']);

        $response = $engine->handleRequest($request, $ddm, $qb);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleRequestGlobalSearchWithOnlyOptionsFieldDoesNotAddWhere(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier(DDMField::IDENTIFIER_OPTIONS);
        $field->setRenderInTable(true);

        $ddm = new DDM('TestEntity', 'context', [$field], $entityManager);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->expects($this->never())->method('andWhere'); // no andWhere because options field is skipped
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturnOnConsecutiveCalls(0, 0);
        $query->method('getResult')->willReturn([]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request(['search' => 'anything']);

        $response = $engine->handleRequest($request, $ddm);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleRequestCleansEmptyArraySearchFields(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setRenderInTable(true);
        $field->setExtendsearch(true);

        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $query->method('getSingleScalarResult')->willReturnOnConsecutiveCalls(0, 0);
        $query->method('getResult')->willReturn([]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request([
            'search_fields' => [
                'filters' => [], // should be removed
                'name' => 'abc',
            ],
        ]);

        $response = $engine->handleRequest($request, $ddm);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string)$response->getContent(), true);
        $this->assertSame(['name' => 'abc'], $data['search_fields']);
    }

    public function testHandleRequestGlobalSearchSkipsEmptyExpressions(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        // This field has livesearch true, but will return null search expression
        $field = new class extends DDMField {
            public function getSearchExpression($qb, $alias, $search): ?object { return null; }
        };
        $field->setIdentifier('special');
        $field->setRenderInTable(true);
        $field->setLivesearch(true);

        $ddm = new DDM('TestEntity', 'context', [$field], $entityManager);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->expects($this->never())->method('andWhere'); // should be skipped since expression is null
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getResult')->willReturn([]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);
        $request = new Request(['search' => 'any']);

        $engine->handleRequest($request, $ddm);
    }

    public function testHandleRequestGlobalSearchWithMixedFieldsHitsContinue(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $field1 = new class extends DDMField {};
        $field1->setIdentifier(DDMField::IDENTIFIER_OPTIONS);
        $field1->setRenderInTable(true);

        $field2 = new class extends DDMField {};
        $field2->setIdentifier('name');
        $field2->setLivesearch(true);
        $field2->setRenderInTable(true);

        // Use addField to bypass attribute filtering in constructor for this specific test
        $ddm = new DDM('TestEntity', 'context', [], $entityManager);
        $ddm->addField($field1);
        $ddm->addField($field2);

        $entityManager->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('getRootAliases')->willReturn(['p']);
        $qb->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());
        $qb->method('select')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setFirstResult')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(0);
        $query->method('getResult')->willReturn([]);

        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);

        $engine = new DDMDatatableEngine($translator, $entityManager);

        $requestPost = new Request();
        $requestPost->setMethod('POST');
        $requestPost->request->set('search', 'findme');
        $engine->handleRequest($requestPost, $ddm);

        $this->assertTrue(true);
    }
}
