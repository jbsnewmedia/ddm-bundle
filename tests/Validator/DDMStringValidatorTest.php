<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Validator;

use JBSNewMedia\DDMBundle\Validator\DDMStringValidator;
use PHPUnit\Framework\TestCase;

final class DDMStringValidatorTest extends TestCase
{
    private DDMStringValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DDMStringValidator();
    }

    public function testSettersAndGetters(): void
    {
        $this->validator->setMinLength(5);
        $this->assertSame(5, $this->validator->getMinLength());

        $this->validator->setMaxLength(10);
        $this->assertSame(10, $this->validator->getMaxLength());
    }

    public function testIsRequired(): void
    {
        $this->assertFalse($this->validator->isRequired());
        $this->validator->setMinLength(0);
        $this->assertFalse($this->validator->isRequired());
        $this->validator->setMinLength(1);
        $this->assertTrue($this->validator->isRequired());
    }

    public function testValidateMinLength(): void
    {
        $this->validator->setMinLength(5);
        $this->assertFalse($this->validator->validate('1234'));
        $this->assertSame('error.ddm.validator.string.min_length', $this->validator->getErrorMessage());
        $this->assertTrue($this->validator->validate('12345'));
    }

    public function testValidateMaxLength(): void
    {
        $this->validator->setMaxLength(5);
        $this->assertFalse($this->validator->validate('123456'));
        $this->assertSame('error.ddm.validator.string.max_length', $this->validator->getErrorMessage());
        $this->assertTrue($this->validator->validate('12345'));
    }

    public function testValidateScalarAndToString(): void
    {
        $this->validator->setMinLength(3);
        $this->assertTrue($this->validator->validate(123));

        $obj = new class {
            public function __toString(): string
            {
                return 'test';
            }
        };
        $this->assertTrue($this->validator->validate($obj));

        $this->assertFalse($this->validator->validate([]));
    }
}
