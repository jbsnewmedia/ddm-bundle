<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Validator;

use JBSNewMedia\DDMBundle\Validator\DDMRequiredValidator;
use PHPUnit\Framework\TestCase;

final class DDMRequiredValidatorTest extends TestCase
{
    private DDMRequiredValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DDMRequiredValidator();
    }

    public function testIsRequired(): void
    {
        $this->assertTrue($this->validator->isRequired());
    }

    public function testValidateWithNull(): void
    {
        $this->assertFalse($this->validator->validate(null));
        $this->assertSame('required', $this->validator->getErrorMessage());
    }

    public function testValidateWithEmptyString(): void
    {
        $this->assertFalse($this->validator->validate(''));
        $this->assertFalse($this->validator->validate('   '));
        $this->assertSame('required', $this->validator->getErrorMessage());
    }

    public function testValidateWithEmptyArray(): void
    {
        $this->assertFalse($this->validator->validate([]));
        $this->assertSame('required', $this->validator->getErrorMessage());
    }

    public function testValidateWithValidValue(): void
    {
        $this->assertTrue($this->validator->validate('value'));
        $this->assertTrue($this->validator->validate(123));
        $this->assertTrue($this->validator->validate(['item']));
    }

    public function testValidateWithCustomErrorMessage(): void
    {
        $this->validator->setErrorMessage('Custom Error');
        $this->assertFalse($this->validator->validate(null));
        $this->assertSame('Custom Error', $this->validator->getErrorMessage());
    }
}
