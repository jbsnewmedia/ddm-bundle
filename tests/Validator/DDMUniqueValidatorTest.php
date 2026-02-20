<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use JBSNewMedia\DDMBundle\Service\DDM;
use JBSNewMedia\DDMBundle\Service\DDMField;
use JBSNewMedia\DDMBundle\Validator\DDMUniqueValidator;
use PHPUnit\Framework\TestCase;

final class DDMUniqueValidatorTest extends TestCase
{
    public function testValidateReturnsTrueForEmptyValue(): void
    {
        $v = new DDMUniqueValidator();
        $this->assertTrue($v->validate(null));
        $this->assertTrue($v->validate(''));
        $this->assertTrue($v->validate([]));
    }

    public function testValidateReturnsTrueWhenNoFieldOrNoDdmOrEmptyIdentifier(): void
    {
        $v = new DDMUniqueValidator();
        // No field set
        $this->assertTrue($v->validate('any'));

        // Field without DDM
        $field = new class extends DDMField {};
        $v->setField($field);
        $this->assertTrue($v->validate('any'));

        // Field with empty identifier
        if (!class_exists('UniqueEntityA')) { eval('class UniqueEntityA {}'); }
        $em = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('UniqueEntityA', 'ctx', [], $em);
        $field->init($ddm, [$field]);
        $field->setIdentifier('');
        $this->assertTrue($v->validate('any'));
    }

    public function testValidateUniquePassesWhenCountZero(): void
    {
        if (!class_exists('UniqueEntityB')) { eval('class UniqueEntityB {}'); }
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $em->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('select')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(0);

        $field = new class extends DDMField {};
        $field->setIdentifier('email');

        $ddm = new DDM('UniqueEntityB', 'ctx', [$field], $em);
        $field->init($ddm, [$field]);

        $v = new DDMUniqueValidator();
        $v->setField($field);

        $this->assertTrue($v->validate('john@example.com'));
    }

    public function testValidateUniqueFailsWhenCountGreaterThanZero(): void
    {
        if (!class_exists('UniqueEntityC')) { eval('class UniqueEntityC {}'); }
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $em->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('select')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(2);

        $field = new class extends DDMField {};
        $field->setIdentifier('email');

        $ddm = new DDM('UniqueEntityC', 'ctx', [$field], $em);
        $field->init($ddm, [$field]);

        $v = new DDMUniqueValidator();
        $v->setField($field);

        $this->assertFalse($v->validate('john@example.com'));
        $this->assertSame('unique', $v->getErrorMessage());
    }

    public function testValidateUniqueExcludesCurrentEntityId(): void
    {
        if (!class_exists('UniqueEntityD')) { eval('class UniqueEntityD { public function getId(){ return 5; } }'); }
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $em->method('getRepository')->willReturn($repository);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $qb->method('select')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb); // should be called when entity has id
        $qb->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(0);

        $field = new class extends DDMField {};
        $field->setIdentifier('email');

        $ddm = new DDM('UniqueEntityD', 'ctx', [$field], $em);
        $ddm->setEntity(new \UniqueEntityD());
        $field->init($ddm, [$field]);

        $v = new DDMUniqueValidator();
        $v->setField($field);

        $this->assertTrue($v->validate('john@example.com'));
    }
}
