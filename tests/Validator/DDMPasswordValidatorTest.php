<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Validator;

use JBSNewMedia\DDMBundle\Validator\DDMPasswordValidator;
use PHPUnit\Framework\TestCase;

class DDMPasswordValidatorTest extends TestCase
{
    public function testPasswordWithSpecialCharPasses(): void
    {
        $validator = new DDMPasswordValidator();
        $validator->setRequireSpecialChars(true);

        $this->assertTrue($validator->validate('12345678aA$'), 'Password with $ should be valid');
        $this->assertTrue($validator->validate('12345678aA_'), 'Password with _ should be valid');
        $this->assertTrue($validator->validate('12345678aA-'), 'Password with - should be valid');
        $this->assertFalse($validator->validate('12345678aA '), 'Password with trailing space is trimmed, so no special char — invalid');
        $this->assertFalse($validator->validate('12345678aAä'), 'Password with ä should be invalid (it is a letter, not a special char)');
    }

    public function testPasswordWithoutSpecialCharFails(): void
    {
        $validator = new DDMPasswordValidator();
        $validator->setRequireSpecialChars(true);

        $this->assertFalse($validator->validate('12345678aA'), 'Password without special char should be invalid');
        $this->assertEquals('password.require_special_chars', $validator->getErrorMessage());
    }

    public function testPasswordWithOtherSpecialChars(): void
    {
        $validator = new DDMPasswordValidator();
        $validator->setRequireSpecialChars(true);

        $this->assertTrue($validator->validate('Password123!'), 'Password with ! should be valid');
        $this->assertTrue($validator->validate('Password123@'), 'Password with @ should be valid');
        $this->assertTrue($validator->validate('Password123#'), 'Password with # should be valid');
        $this->assertTrue($validator->validate('Password123%'), 'Password with % should be valid');
        $this->assertTrue($validator->validate('Password123&'), 'Password with & should be valid');
        $this->assertTrue($validator->validate('Password123*'), 'Password with * should be valid');
    }

    public function testMatchCheckOnlyWithTwoInputs(): void
    {
        $validator = new DDMPasswordValidator();

        // Single value (no array) — no match check
        $this->assertTrue($validator->validate('12345678aA$'));

        // Array with 1 element — no match check
        $this->assertTrue($validator->validate(['12345678aA$']));

        // Array with 2 matching values — valid
        $this->assertTrue($validator->validate(['12345678aA$', '12345678aA$']));

        // Array with 2 non-matching values — invalid
        $this->assertFalse($validator->validate(['12345678aA$', 'different1B!']));
        $this->assertEquals('password.match_error', $validator->getErrorMessage());
    }
}
