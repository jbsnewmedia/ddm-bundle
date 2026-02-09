<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Attribute;

use JBSNewMedia\DDMBundle\Attribute\DDMFieldAttribute;
use PHPUnit\Framework\TestCase;

final class DDMFieldAttributeTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $attribute = new DDMFieldAttribute();
        $this->assertNull($attribute->entity);
        $this->assertNull($attribute->identifier);
        $this->assertNull($attribute->setTo);
        $this->assertSame(100, $attribute->order);
    }

    public function testConstructorWithCustomValues(): void
    {
        $attribute = new DDMFieldAttribute(
            entity: 'User',
            identifier: 'id',
            setTo: 'username',
            order: 200
        );
        $this->assertSame('User', $attribute->entity);
        $this->assertSame('id', $attribute->identifier);
        $this->assertSame('username', $attribute->setTo);
        $this->assertSame(200, $attribute->order);
    }
}
