<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Validator;

use JBSNewMedia\DDMBundle\Validator\DDMUniqueValidator;
use PHPUnit\Framework\TestCase;

final class DDMUniqueValidatorTest extends TestCase
{
    public function testValidate(): void
    {
        $validator = new DDMUniqueValidator();
        $this->assertTrue($validator->validate('anything'));
        $this->assertTrue($validator->validate(null));
    }
}
