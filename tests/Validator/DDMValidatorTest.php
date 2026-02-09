<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Validator;

use JBSNewMedia\DDMBundle\Validator\DDMValidator;
use PHPUnit\Framework\TestCase;

final class DDMValidatorTest extends TestCase
{
    private DDMValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new class extends DDMValidator {
            public function validate(mixed $value): bool
            {
                return true;
            }
        };
    }

    public function testDefaultValues(): void
    {
        $this->assertNull($this->validator->getErrorMessage());
        $this->assertNull($this->validator->getAlias());
        $this->assertSame(100, $this->validator->getPriority());
        $this->assertFalse($this->validator->isRequired());
    }

    public function testSettersAndGetters(): void
    {
        $this->validator->setErrorMessage('Custom Error');
        $this->assertSame('Custom Error', $this->validator->getErrorMessage());

        $this->validator->setAlias('custom_alias');
        $this->assertSame('custom_alias', $this->validator->getAlias());

        $this->validator->setPriority(200);
        $this->assertSame(200, $this->validator->getPriority());
    }
}
