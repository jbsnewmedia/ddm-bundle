<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Validator;

use JBSNewMedia\DDMBundle\Validator\DDMEmailValidator;
use PHPUnit\Framework\TestCase;

final class DDMEmailValidatorTest extends TestCase
{
    public function testValidateEmptyIsAllowed(): void
    {
        $v = new DDMEmailValidator();
        $this->assertTrue($v->validate(null));
        $this->assertTrue($v->validate(''));
        $this->assertTrue($v->validate([]));
    }

    public function testValidateInvalidEmailSetsMessage(): void
    {
        $v = new DDMEmailValidator();
        $this->assertFalse($v->validate('not-an-email'));
        $this->assertSame('email.invalid', $v->getErrorMessage());
    }

    public function testValidateValidEmail(): void
    {
        $v = new DDMEmailValidator();
        $this->assertTrue($v->validate('john.doe@example.com'));
    }

    public function testValidateNonScalar(): void
    {
        $v = new DDMEmailValidator();
        // Passing an array which is not scalar and not empty (count > 0)
        $this->assertFalse($v->validate(['not', 'an', 'email']));
        $this->assertSame('email.invalid', $v->getErrorMessage());

        $this->assertFalse($v->validate(new \stdClass()));
        $this->assertSame('email.invalid', $v->getErrorMessage());
    }

    public function testCustomErrorMessageOverridesDefault(): void
    {
        $v = new DDMEmailValidator();
        $v->setErrorMessage('custom.msg');
        $this->assertFalse($v->validate('wrong'));
        $this->assertSame('custom.msg', $v->getErrorMessage());
    }

    public function testAliasAndIsRequired(): void
    {
        $v = new DDMEmailValidator();
        $this->assertSame('email', $v->getAlias());
        $this->assertFalse($v->isRequired());
    }
}
