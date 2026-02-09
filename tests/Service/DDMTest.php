<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Service;

use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;
use JBSNewMedia\DDMBundle\Service\DDM;
use JBSNewMedia\DDMBundle\Service\DDMField;
use PHPUnit\Framework\TestCase;

final class DDMTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $ddm = new DDM('EntityClass', 'context', []);

        $ddm->setFormTemplate('form.html.twig');
        $this->assertSame('form.html.twig', $ddm->getFormTemplate());

        $ddm->setDatatableTemplate('table.html.twig');
        $this->assertSame('table.html.twig', $ddm->getDatatableTemplate());

        $ddm->setTemplate('both.html.twig');
        $this->assertSame('both.html.twig', $ddm->getFormTemplate());
        $this->assertSame('both.html.twig', $ddm->getDatatableTemplate());

        $this->assertSame('EntityClass', $ddm->getEntityClass());
    }

    public function testLoadFieldsWithAttributes(): void
    {
        if (!class_exists('MyEntity')) {
            eval('class MyEntity {}');
        }
        $field = new #[DDMFieldAttribute(entity: 'MyEntity', order: 10)] class extends DDMField {};
        $field->setIdentifier('field1');

        $fieldNoMatch = new #[DDMFieldAttribute(entity: 'OtherEntity')] class extends DDMField {};

        $ddm = new DDM('MyEntity', 'context', [$field, $fieldNoMatch]);

        $this->assertCount(1, $ddm->getFields());
        $this->assertSame(10, $ddm->getFields()[0]->getOrder());
    }

    public function testLoadFieldsWithContextMatch(): void
    {
        $field = new #[DDMFieldAttribute(identifier: 'my_context')] class extends DDMField {};
        $field->setIdentifier('field1');

        $ddm = new DDM('Entity', 'my_context', [$field]);

        $this->assertCount(1, $ddm->getFields());
    }

    public function testAddField(): void
    {
        $ddm = new DDM('Entity', 'context', []);
        $field = new class extends DDMField {};
        $ddm->addField($field);

        $this->assertCount(1, $ddm->getFields());
    }
}
