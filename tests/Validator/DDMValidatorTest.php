<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Validator;

use JBSNewMedia\DDMBundle\Service\DDMField;
use JBSNewMedia\DDMBundle\Validator\DDMValidator;
use PHPUnit\Framework\TestCase;

final class DDMValidatorTest extends TestCase
{
    public function testBaseGettersSetters(): void
    {
        $validator = new class extends DDMValidator {
            public function validate(mixed $value): bool { return true; }
        };

        // Defaults
        $this->assertNull($validator->getErrorMessage());
        $this->assertSame([], $validator->getErrorMessageParameters());
        $this->assertNull($validator->getAlias());
        $this->assertSame(DDMValidator::DEFAULT_PRIORITY, $validator->getPriority());
        $this->assertFalse($validator->isRequired());
        $this->assertNull($validator->getField());

        // Setters
        $validator->setErrorMessage('msg');
        $validator->setErrorMessageParameters(['{a}' => 'b']);
        $validator->setAlias('alias');
        $validator->setPriority(200);

        $field = new class extends DDMField {};
        $validator->setField($field);

        // Assertions
        $this->assertSame('msg', $validator->getErrorMessage());
        $this->assertSame(['{a}' => 'b'], $validator->getErrorMessageParameters());
        $this->assertSame('alias', $validator->getAlias());
        $this->assertSame(200, $validator->getPriority());
        $this->assertSame($field, $validator->getField());
    }
}
