<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\DDMBundle\Service\DDM;
use JBSNewMedia\DDMBundle\Service\DDMDatatableFormHandler;
use JBSNewMedia\DDMBundle\Service\DDMField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class DDMDatatableFormHandlerTest extends TestCase
{
    public function testHandleGetRequest(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setRenderInForm(true);

        if (!class_exists('Entity')) { eval('class Entity {}'); }
        $ddm = new DDM('Entity', 'context', [$field], $entityManager);

        $twig->method('render')->willReturn('form_html');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request();

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('form_html', $response->getContent());
    }

    public function testHandlePostRequestValidationError(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('ValidationErrorEntity')) {
            eval('class ValidationErrorEntity {}');
        }

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setName('Name');
        $field->setRenderInForm(true);
        $validator = new class extends \JBSNewMedia\DDMBundle\Validator\DDMValidator {
            public function validate(mixed $value): bool { return false; }
            public function getErrorMessage(): ?string { return 'Invalid name'; }
        };
        $field->addValidator($validator);

        if (!class_exists('ValidationErrorEntity')) { eval('class ValidationErrorEntity {}'); }
        $ddm = new DDM('ValidationErrorEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['name' => 'some-value']);
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string)$response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('', $data['invalid']['name']);
    }

    public function testHandlePreload(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('PreloadEntity')) {
            eval('class PreloadEntity { public function getName() { return "Preloaded"; } }');
        }

        $entity = new \PreloadEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setRenderInForm(true);

        if (!class_exists('PreloadEntity')) { eval('class PreloadEntity { public function getName() { return "Preloaded"; } }'); }
        $ddm = new DDM('PreloadEntity', 'context', [], $entityManager);
        $ddm->addField($field);
        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        $this->assertSame('Preloaded', $field->getValue());
    }

    public function testHandlePostRequestSuccess(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('FormEntity')) {
            eval('class FormEntity { public function setName($name) {} }');
        }

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setRenderInForm(true);

        if (!class_exists('FormEntity')) { eval('class FormEntity { public function setName($name) {} }'); }
        $ddm = new DDM('FormEntity', 'context', [$field], $entityManager);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['name' => 'John']);
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testHandlePostRequestRequiredFieldMissing(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $translator->method('trans')->willReturnCallback(fn ($key) => $key);

        if (!class_exists('RequiredFieldEntity')) {
            eval('class RequiredFieldEntity {}');
        }

        $field = new class extends DDMField {};
        $field->setIdentifier('email');
        $field->setName('Email');
        $field->setRenderInForm(true);
        $validator = new class extends \JBSNewMedia\DDMBundle\Validator\DDMValidator {
            public function validate(mixed $value): bool { return !empty($value); }
            public function getErrorMessage(): ?string { return 'Required'; }
        };
        $field->addValidator($validator);

        if (!class_exists('RequiredFieldEntity')) { eval('class RequiredFieldEntity {}'); }
        $ddm = new DDM('RequiredFieldEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['email' => '']); // Empty value
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string)$response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('email', $data['invalid']);
    }

    public function testHandlePostRequestWithTranslationDomain(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $translator->method('trans')->willReturnCallback(fn ($key) => $key);

        if (!class_exists('TranslatedEntity')) {
            eval('class TranslatedEntity { public function setTitle($t) {} }');
        }

        $field = new class extends DDMField {};
        $field->setIdentifier('title');
        $field->setName('Title');
        $field->setRenderInForm(true);

        if (!class_exists('TranslatedEntity')) { eval('class TranslatedEntity {}'); }
        $ddm = new DDM('TranslatedEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['title' => 'Test Title']);
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm, null, false, '', ['translation_domain' => 'messages']);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testHandleWithExistingEntity(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $translator->method('trans')->willReturnCallback(fn ($key) => $key);

        if (!class_exists('ExistingEntity')) {
            eval('class ExistingEntity { private $name = "Old"; public function getName() { return $this->name; } public function setName($n) { $this->name = $n; } }');
        }

        $entity = new \ExistingEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setName('Name');
        $field->setRenderInForm(true);

        if (!class_exists('ExistingEntity')) { eval('class ExistingEntity {}'); }
        $ddm = new DDM('ExistingEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['name' => 'Updated']);
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm, $entity, false);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testHandleWithPreloadTrue(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $translator->method('trans')->willReturnCallback(fn ($key) => $key);

        if (!class_exists('PreloadTrueEntity')) {
            eval('class PreloadTrueEntity { private $name; public function getName() { return $this->name; } public function setName($n) { $this->name = $n; } }');
        }

        $entity = new \PreloadTrueEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setName('Name');
        $field->setRenderInForm(true);

        if (!class_exists('PreloadTrueEntity')) { eval('class PreloadTrueEntity { public function getName() { return "X"; } }'); }
        $ddm = new DDM('PreloadTrueEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['name' => 'New Value']);
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm, $entity, true);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testHandleWithValidatorDefaultError(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $translator->method('trans')->willReturnCallback(fn ($key) => $key);

        if (!class_exists('DefaultErrorEntity')) {
            eval('class DefaultErrorEntity {}');
        }

        $field = new class extends DDMField {};
        $field->setIdentifier('code');
        $field->setName('Code');
        $field->setRenderInForm(true);
        $validator = new class extends \JBSNewMedia\DDMBundle\Validator\DDMValidator {
            public function validate(mixed $value): bool { return false; }
            public function getErrorMessage(): ?string { return null; } // Triggers default error
        };
        $field->addValidator($validator);

        if (!class_exists('DefaultErrorEntity')) { eval('class DefaultErrorEntity {}'); }
        $ddm = new DDM('DefaultErrorEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['code' => 'invalid-code']);
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string)$response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('', $data['invalid']['code']);
    }

    public function testHandleWithFieldNotInForm(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $twig->method('render')->willReturn('rendered');

        if (!class_exists('NotInFormEntity')) {
            eval('class NotInFormEntity {}');
        }

        $field = new class extends DDMField {};
        $field->setIdentifier('hidden');
        $field->setRenderInForm(false);

        if (!class_exists('NotInFormEntity')) { eval('class NotInFormEntity {}'); }
        $ddm = new DDM('NotInFormEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request();

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testHandleWithCustomTemplate(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $twig->method('render')->willReturn('custom_template_rendered');

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setRenderInForm(true);

        $ddm = new DDM('Entity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request();

        $response = $handler->handle($request, $ddm, null, false, 'custom/template.html.twig');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('custom_template_rendered', $response->getContent());
    }

    public function testHandleWithEntityValueObject(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('StringableValue')) {
            eval('class StringableValue { public function __toString(): string { return "stringified"; } }');
        }

        if (!class_exists('ValueObjectEntity')) {
            eval('class ValueObjectEntity { public function getStatus() { return new StringableValue(); } }');
        }

        $entity = new \ValueObjectEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('status');
        $field->setRenderInForm(true);

        if (!class_exists('ValueObjectEntity')) { eval('class ValueObjectEntity { public function setName($n) {} }'); }
        $ddm = new DDM('ValueObjectEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        $this->assertSame('', $field->getValue());
    }

    public function testHandleWithMethodNotExists(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('NoMethodEntity')) {
            eval('class NoMethodEntity {}');
        }

        $entity = new \NoMethodEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('nonexistent');
        $field->setRenderInForm(true);

        if (!class_exists('NoMethodEntity')) { eval('class NoMethodEntity {}'); }
        $ddm = new DDM('NoMethodEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        $this->assertNull($field->getValue());
    }

    public function testHandleWithDdmFormTemplate(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $twig->method('render')->willReturn('ddm_template');

        $field = new class extends DDMField {};
        $field->setIdentifier('name');
        $field->setRenderInForm(true);

        $ddm = new DDM('Entity', 'context', [], $entityManager);
        $ddm->addField($field);
        $ddm->setFormTemplate('ddm/custom.html.twig');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request();

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testHandlePostWithSetMethodNotExists(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $translator->method('trans')->willReturnCallback(fn ($key) => $key);

        if (!class_exists('NoSetMethodEntity')) {
            eval('class NoSetMethodEntity {}');
        }

        $field = new class extends DDMField {};
        $field->setIdentifier('readonly');
        $field->setName('Readonly');
        $field->setRenderInForm(true);

        if (!class_exists('NoSetMethodEntity')) { eval('class NoSetMethodEntity { public function getName() { return null; } }'); }
        $ddm = new DDM('NoSetMethodEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['readonly' => 'value']);
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testHandleWithEntityNonScalarNonStringableValue(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('NonStringableObject')) {
            eval('class NonStringableObject {}'); // No __toString
        }

        if (!class_exists('NonScalarValueEntity')) {
            eval('class NonScalarValueEntity { public function getData() { return new NonStringableObject(); } }');
        }

        $entity = new \NonScalarValueEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('data');
        $field->setRenderInForm(true);

        if (!class_exists('NonScalarValueEntity')) { eval('class NonScalarValueEntity { public function setName($n) {} }'); }
        $ddm = new DDM('NonScalarValueEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        // Value should be empty string because it's not scalar
        $this->assertSame('', $field->getValue());
    }

    public function testHandleWithEntityArrayValue(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('ArrayValueEntity')) {
            eval('class ArrayValueEntity { public function getTags() { return ["tag1", "tag2"]; } }');
        }

        $entity = new \ArrayValueEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('tags');
        $field->setRenderInForm(true);

        if (!class_exists('ArrayValueEntity')) { eval('class ArrayValueEntity { public function setName($n) {} }'); }
        $ddm = new DDM('ArrayValueEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        // Array is not scalar, so value should be empty string
        $this->assertSame('', $field->getValue());
    }

    public function testHandleWithEntityNullValue(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('NullValueEntity')) {
            eval('class NullValueEntity { public function getNullable() { return null; } }');
        }

        $entity = new \NullValueEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('nullable');
        $field->setRenderInForm(true);

        if (!class_exists('NullValueEntity')) { eval('class NullValueEntity { public function setName($n) {} }'); }
        $ddm = new DDM('NullValueEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        // null is not scalar, so value should not be set
        $this->assertNull($field->getValue());
    }

    public function testHandleWithEntityIntegerValue(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('IntegerValueEntity')) {
            eval('class IntegerValueEntity { public function getCount() { return 42; } }');
        }

        $entity = new \IntegerValueEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('count');
        $field->setRenderInForm(true);

        if (!class_exists('IntegerValueEntity')) { eval('class IntegerValueEntity { public function setName($n) {} }'); }
        $ddm = new DDM('IntegerValueEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        // Integer is scalar, value should be string "42"
        $this->assertSame('42', $field->getValue());
    }

    public function testHandleWithEntityBooleanValue(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('BooleanValueEntity')) {
            eval('class BooleanValueEntity { public function getActive() { return true; } }');
        }

        $entity = new \BooleanValueEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('active');
        $field->setRenderInForm(true);

        if (!class_exists('BooleanValueEntity')) { eval('class BooleanValueEntity { public function setName($n) {} }'); }
        $ddm = new DDM('BooleanValueEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        // Boolean is scalar, value should be "1" for true
        $this->assertSame('1', $field->getValue());
    }

    public function testHandleWithEntityFieldNotRenderInForm(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        if (!class_exists('HiddenFieldEntity')) {
            eval('class HiddenFieldEntity { public function getHidden() { return "secret"; } }');
        }

        $entity = new \HiddenFieldEntity();

        $field = new class extends DDMField {};
        $field->setIdentifier('hidden');
        $field->setRenderInForm(false);

        if (!class_exists('HiddenFieldEntity')) { eval('class HiddenFieldEntity {}'); }
        $ddm = new DDM('HiddenFieldEntity', 'context', [], $entityManager);
        $ddm->addField($field);

        $twig->method('render')->willReturn('form');

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $handler->handle(new Request(), $ddm, $entity, false);

        // Field not rendered in form, value should not be loaded from entity
        $this->assertNull($field->getValue());
    }

    public function testHandlePostWithMixedRenderInFormFields(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $twig = $this->createMock(Environment::class);

        $translator->method('trans')->willReturnCallback(fn ($key) => $key);

        if (!class_exists('MixedRenderEntity')) {
            eval('class MixedRenderEntity { public function setVisible($v) {} public function setHidden($h) {} }');
        }

        $visibleField = new class extends DDMField {};
        $visibleField->setIdentifier('visible');
        $visibleField->setName('Visible');
        $visibleField->setRenderInForm(true);

        $hiddenField = new class extends DDMField {};
        $hiddenField->setIdentifier('hidden');
        $hiddenField->setName('Hidden');
        $hiddenField->setRenderInForm(false);

        if (!class_exists('MixedRenderEntity')) { eval('class MixedRenderEntity {}'); }
        $ddm = new DDM('MixedRenderEntity', 'context', [], $entityManager);
        $ddm->addField($visibleField);
        $ddm->addField($hiddenField);

        $handler = new DDMDatatableFormHandler($entityManager, $translator, $twig);
        $request = new Request([], ['visible' => 'value1', 'hidden' => 'value2']);
        $request->setMethod('POST');

        $response = $handler->handle($request, $ddm);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\JsonResponse::class, $response);
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
    }
}
