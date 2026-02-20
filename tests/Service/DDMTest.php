<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;
use JBSNewMedia\DDMBundle\Service\DDM;
use JBSNewMedia\DDMBundle\Service\DDMField;
use PHPUnit\Framework\TestCase;

final class DDMTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        if (!class_exists('EntityClass')) { eval('class EntityClass {}'); }
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('EntityClass', 'context', [], $entityManager);

        $ddm->setFormTemplate('form.html.twig');
        $this->assertSame('form.html.twig', $ddm->getFormTemplate());

        $ddm->setDatatableTemplate('table.html.twig');
        $this->assertSame('table.html.twig', $ddm->getDatatableTemplate());

        $ddm->setTemplate('both.html.twig');
        $this->assertSame('both.html.twig', $ddm->getFormTemplate());
        $this->assertSame('both.html.twig', $ddm->getDatatableTemplate());

        $this->assertSame('EntityClass', $ddm->getEntityClass());
        $this->assertSame($entityManager, $ddm->getEntityManager());
    }

    public function testLoadFieldsWithAttributes(): void
    {
        if (!class_exists('MyEntity')) {
            eval('class MyEntity {}');
        }
        $field = new #[DDMFieldAttribute(entity: 'MyEntity', order: 10)] class extends DDMField {};
        $field->setIdentifier('field1');

        $fieldNoMatch = new #[DDMFieldAttribute(entity: 'OtherEntity')] class extends DDMField {};

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('MyEntity', 'context', [$field, $fieldNoMatch], $entityManager);

        $this->assertCount(1, $ddm->getFields());
        $this->assertSame(10, $ddm->getFields()[0]->getOrder());
    }

    public function testLoadFieldsWithContextMatch(): void
    {
        $field = new #[DDMFieldAttribute(identifier: 'my_context')] class extends DDMField {};
        $field->setIdentifier('field1');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('Entity', 'my_context', [$field], $entityManager);

        $this->assertCount(1, $ddm->getFields());
    }

    public function testAddField(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('Entity', 'context', [], $entityManager);
        $field = new class extends DDMField {};
        $ddm->addField($field);

        $this->assertCount(1, $ddm->getFields());
    }

    public function testRoutesPropagationAndGetRoute(): void
    {
        if (!class_exists('RouteEntity')) { eval('class RouteEntity {}'); }
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $field = new class extends DDMField {};
        $ddm = new DDM('RouteEntity', 'ctx', [], $entityManager);
        $ddm->addField($field);

        $ddm->setRoutes(['show' => '/path']);
        $this->assertSame('/path', $ddm->getRoute('show'));
        $this->assertSame('/path', $ddm->getFields()[0]->getRoute('show'));
    }

    public function testEntitySetAndIdAndTitle(): void
    {
        if (!class_exists('EntityWithId')) { eval('class EntityWithId { public function getId() { return 7; } }'); }
        $entity = new \EntityWithId();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('EntityWithId', 'my_ctx', [], $entityManager);

        $this->assertNull($ddm->getEntity());
        $this->assertNull($ddm->getEntityId());

        $ddm->setEntity($entity);
        $this->assertSame($entity, $ddm->getEntity());
        $this->assertSame(7, $ddm->getEntityId());

        $entityNoId = new \stdClass();
        $ddm->setEntity($entityNoId);
        $this->assertNull($ddm->getEntityId());

        $this->assertSame('my_ctx', $ddm->getContext());
        $this->assertNull($ddm->getTitle());
        $ddm->setTitle('T');
        $this->assertSame('T', $ddm->getTitle());
    }

    public function testLoadFieldsAttributeShortNameMatch(): void
    {
        if (!class_exists('ShortNameEntity')) { eval('class ShortNameEntity {}'); }
        $field = new #[DDMFieldAttribute(entity: 'shortnameentity')] class extends DDMField {};
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $ddm = new DDM('ShortNameEntity', 'ctx', [$field], $entityManager);
        $this->assertCount(1, $ddm->getFields());
    }

    public function testLoadFieldsAttributeContextViaEntityField(): void
    {
        if (!class_exists('AnyEntity')) { eval('class AnyEntity {}'); }
        $field = new #[DDMFieldAttribute(entity: 'my_context')] class extends DDMField {};
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $ddm = new DDM('AnyEntity', 'my_context', [$field], $entityManager);
        $this->assertCount(1, $ddm->getFields());
    }

    public function testNoAttributeMatchResultsInEmptyFields(): void
    {
        if (!class_exists('NOMatchEntity')) { eval('class NOMatchEntity {}'); }
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $fieldOther = new #[DDMFieldAttribute(entity: 'OtherEntity')] class extends DDMField {};
        $ddm = new DDM('NOMatchEntity', 'ctx', [$fieldOther], $entityManager);

        $this->assertCount(0, $ddm->getFields());
    }

    public function testSetRoutesBeforeAndAfterAddField(): void
    {
        if (!class_exists('RouteEntity2')) { eval('class RouteEntity2 {}'); }
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('RouteEntity2', 'ctx', [], $entityManager);

        // Set routes before adding fields -> no propagation to later fields
        $ddm->setRoutes(['list' => '/list']);

        $field = new class extends DDMField {};
        $ddm->addField($field);
        $this->assertNull($ddm->getFields()[0]->getRoute('list'));

        // Now set routes again -> propagation applies
        $ddm->setRoutes(['list' => '/list', 'show' => '/show']);
        $this->assertSame('/list', $ddm->getFields()[0]->getRoute('list'));
        $this->assertSame('/show', $ddm->getFields()[0]->getRoute('show'));

        // Unknown key returns null
        $this->assertNull($ddm->getRoute('unknown'));
    }

    public function testGetRoutesDefaultIsEmptyArray(): void
    {
        if (!class_exists('RouteEntity3')) { eval('class RouteEntity3 {}'); }
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('RouteEntity3', 'ctx', [], $entityManager);
        $this->assertSame([], $ddm->getRoutes());
    }

    public function testGetFormTemplateDefaultIsNull(): void
    {
        if (!class_exists('TemplateEntity')) { eval('class TemplateEntity {}'); }
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $ddm = new DDM('TemplateEntity', 'ctx', [], $entityManager);
        $this->assertNull($ddm->getFormTemplate());
        $this->assertNull($ddm->getDatatableTemplate());
        $this->assertNull($ddm->getTitle());
    }
}
