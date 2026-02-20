<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Service;

use JBSNewMedia\DDMBundle\Service\DDMField;
use JBSNewMedia\DDMBundle\Validator\DDMValidator;
use PHPUnit\Framework\TestCase;

final class DDMFieldTest extends TestCase
{
    private DDMField $field;

    protected function setUp(): void
    {
        $this->field = new class extends DDMField {};
    }

    public function testIdentifier(): void
    {
        $this->field->setIdentifier('test_id');
        $this->assertSame('test_id', $this->field->getIdentifier());
    }

    public function testName(): void
    {
        $this->field->setName('Test Name');
        $this->assertSame('Test Name', $this->field->getName());
    }

    public function testRoutes(): void
    {
        $this->assertSame([], $this->field->getRoutes());
        $this->field->setRoutes(['show' => '/x']);
        $this->assertSame('/x', $this->field->getRoute('show'));
    }

    public function testValue(): void
    {
        $this->field->setValue('test_value');
        $this->assertSame('test_value', $this->field->getValue());
        $this->field->setValue(null);
        $this->assertNull($this->field->getValue());
    }

    public function testOrder(): void
    {
        $this->field->setOrder(50);
        $this->assertSame(50, $this->field->getOrder());
    }

    public function testLivesearch(): void
    {
        $this->assertTrue($this->field->isLivesearch());
        $this->field->setLivesearch(false);
        $this->assertFalse($this->field->isLivesearch());
    }

    public function testExtendsearch(): void
    {
        $this->assertTrue($this->field->isExtendsearch());
        $this->field->setExtendsearch(false);
        $this->assertFalse($this->field->isExtendsearch());
    }

    public function testSortable(): void
    {
        $this->assertTrue($this->field->isSortable());
        $this->field->setSortable(false);
        $this->assertFalse($this->field->isSortable());
    }

    public function testRenderInForm(): void
    {
        $this->assertTrue($this->field->isRenderInForm());
        $this->field->setRenderInForm(false);
        $this->assertFalse($this->field->isRenderInForm());
    }

    public function testRenderInTable(): void
    {
        $this->assertTrue($this->field->isRenderInTable());
        $this->field->setRenderInTable(false);
        $this->assertFalse($this->field->isRenderInTable());
    }

    public function testTemplate(): void
    {
        $this->assertSame('@DDM/fields/text.html.twig', $this->field->getTemplate());
        $this->field->setTemplate('test.html.twig');
        $this->assertSame('test.html.twig', $this->field->getTemplate());
    }

    public function testSubFields(): void
    {
        $subField = new class extends DDMField {};
        $this->field->setSubFields([$subField]);
        $this->assertSame([$subField], $this->field->getSubFields());

        $subField2 = new class extends DDMField {};
        $this->field->addSubField($subField2);
        $this->assertCount(2, $this->field->getSubFields());
    }

    public function testValidators(): void
    {
        $validator = new class extends DDMValidator {
            public function validate(mixed $value): bool { return $value === 'valid'; }
        };
        $validator->setAlias('test_validator');

        $this->field->addValidator($validator);
        $this->assertCount(1, $this->field->getValidators());

        $this->assertTrue($this->field->validate('valid'));
        $this->assertFalse($this->field->validate('invalid'));
        $this->assertCount(1, $this->field->getErrors()); // Auch ohne Fehlermeldung wird ein Fehler erfasst

        $validator->setErrorMessage('Error');
        $this->assertFalse($this->field->validate('invalid'));
        $this->assertCount(1, $this->field->getErrors());

        $this->field->removeValidator('test_validator');
        $this->assertCount(0, $this->field->getValidators());
    }

    public function testRenderWithValue(): void
    {
        $entity = new class { public function getTest(): string { return 'static_value'; } };
        $this->field->setIdentifier('test');
        $this->assertSame('static_value', $this->field->renderDatatable($entity));
    }

    public function testIsRequired(): void
    {
        $this->assertFalse($this->field->isRequired());
        $validator = new class extends DDMValidator {
            public function validate(mixed $value): bool { return true; }
            public function isRequired(): bool { return true; }
        };
        $this->field->addValidator($validator);
        $this->assertTrue($this->field->isRequired());
    }

    public function testGetError(): void
    {
        $this->assertNull($this->field->getError());
        $validator = new class extends DDMValidator {
            public function validate(mixed $value): bool { return false; }
            public function getErrorMessage(): ?string { return 'Error Message'; }
        };
        $this->field->addValidator($validator);
        $this->field->validate('any');
        $error = $this->field->getError();
        $this->assertIsArray($error);
        $this->assertSame('Error Message', $error['message']);
        $this->assertSame('ddm_validator_default', $error['domain']);
        $this->assertSame([], $error['parameters']);
    }

    public function testRender(): void
    {
        $entity = new class {
            public function getTest(): string { return 'rendered_value'; }
        };
        $this->field->setIdentifier('test');
        $this->assertSame('rendered_value', $this->field->renderDatatable($entity));

        $entity2 = new class {};
        $this->assertSame('rendered_value', $this->field->renderDatatable($entity2));
    }

    public function testInit(): void
    {
        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        if (!class_exists('InitEntity')) { eval('class InitEntity {}'); }
        $ddm = new \JBSNewMedia\DDMBundle\Service\DDM('InitEntity', 'ctx', [$this->field], $entityManager);
        $allFields = [$this->field];
        $this->field->init($ddm, $allFields);
        $this->assertTrue(true); // Should not throw exception
    }

    public function testRenderArray(): void
    {
        $entity = new class {
            public function getTest(): array { return ['val1', 'val2']; }
        };
        $this->field->setIdentifier('test');
        $this->assertSame(['val1', 'val2'], $this->field->renderForm($entity));
    }

    public function testRenderWithStringableObject(): void
    {
        $entity = new class {
            public function getTest(): object {
                return new class {
                    public function __toString(): string { return 'stringified'; }
                };
            }
        };
        $this->field->setIdentifier('test');
        $this->assertSame('', $this->field->renderDatatable($entity));
    }

    public function testRenderWithNonStringableObject(): void
    {
        $entity = new class {
            public function getTest(): object {
                return new class {};
            }
        };
        $this->field->setIdentifier('test');
        $this->assertSame('', $this->field->renderDatatable($entity));
    }

    public function testRenderWithIntegerValue(): void
    {
        $entity = new class {
            public function getTest(): int { return 42; }
        };
        $this->field->setIdentifier('test');
        $this->assertSame('42', $this->field->renderDatatable($entity));
    }

    public function testValidatorPrioritySorting(): void
    {
        $validator1 = new class extends DDMValidator {
            public function validate(mixed $value): bool { return true; }
        };
        $validator1->setPriority(50);

        $validator2 = new class extends DDMValidator {
            public function validate(mixed $value): bool { return true; }
        };
        $validator2->setPriority(100);

        $this->field->addValidator($validator1);
        $this->field->addValidator($validator2);

        $validators = $this->field->getValidators();
        $this->assertSame(100, $validators[0]->getPriority());
        $this->assertSame(50, $validators[1]->getPriority());
    }

    public function testRemoveValidatorWithoutAlias(): void
    {
        $validator = new class extends DDMValidator {
            public function validate(mixed $value): bool { return true; }
        };

        $this->field->addValidator($validator);
        $this->assertCount(1, $this->field->getValidators());

        $this->field->removeValidator('nonexistent');
        $this->assertCount(1, $this->field->getValidators());
    }

    public function testValueHandlerGetterSetter(): void
    {
        $handler = new \JBSNewMedia\DDMBundle\Value\DDMStringValue();
        $this->field->setValueHandler($handler);
        $this->assertSame($handler, $this->field->getValueHandler());
    }

    public function testRenderSearch(): void
    {
        $entity = new class {
            public function getTest(): string { return 'search_val'; }
        };
        $this->field->setIdentifier('test');
        $this->assertSame('search_val', $this->field->renderSearch($entity));
    }

    public function testIsRenderSearch(): void
    {
        $this->assertTrue($this->field->isRenderSearch());
        $this->field->setRenderSearch(false);
        $this->assertFalse($this->field->isRenderSearch());
    }

    public function testGetDdm(): void
    {
        $this->assertNull($this->field->getDdm());
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        if (!class_exists('DdmEntity')) { eval('class DdmEntity {}'); }
        $ddm = new \JBSNewMedia\DDMBundle\Service\DDM('DdmEntity', 'ctx', [], $em);
        $this->field->init($ddm, []);
        $this->assertSame($ddm, $this->field->getDdm());
    }

    public function testGetSetSubFields(): void
    {
        $sub = new class extends DDMField {};
        $this->field->setSubFields([$sub]);
        $this->assertSame([$sub], $this->field->getSubFields());
    }

    public function testAddSubField(): void
    {
        $sub = new class extends DDMField {};
        $this->field->addSubField($sub);
        $this->assertCount(1, $this->field->getSubFields());
    }

    public function testGetSetTemplate(): void
    {
        $this->field->setTemplate('custom.html.twig');
        $this->assertSame('custom.html.twig', $this->field->getTemplate());
    }

    public function testDeprecatedValueMethods(): void
    {
        $this->field->setValue('val');
        $this->assertSame('val', $this->field->getValue());
    }

    public function testGetSearchExpressionReturnsNullWhenNotLivesearch(): void
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $this->field->setLivesearch(false);
        $this->assertNull($this->field->getSearchExpression($qb, 'p', 'search'));
    }
}
