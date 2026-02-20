<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Value;

use JBSNewMedia\DDMBundle\Value\DDMArrayValue;
use JBSNewMedia\DDMBundle\Value\DDMValue;
use PHPUnit\Framework\TestCase;

final class DDMValueTest extends TestCase
{
    public function testTypeGettersSetters(): void
    {
        $value = new class extends DDMValue {
            private mixed $store = null;
            public function getValue(): mixed { return $this->store; }
            public function setValue(mixed $value): void { $this->store = $value; }
            public function __toString(): string { return (string) $this->store; }
        };

        $this->assertSame('text', $value->getType());
        $value->setType('custom');
        $this->assertSame('custom', $value->getType());

        $value->setValue('x');
        $this->assertSame('x', $value->getValue());
        $this->assertSame('x', (string) $value);
    }

    public function testDDMArrayValue(): void
    {
        $v = new DDMArrayValue(['a', 'b']);
        $this->assertSame(['a', 'b'], $v->getValue());
        $this->assertSame('a, b', (string) $v);

        $v->setValue(['c']);
        $this->assertSame(['c'], $v->getValue());

        $v->setValue(null);
        $this->assertSame([], $v->getValue());

        $v->setValue('scalar');
        $this->assertSame(['scalar'], $v->getValue());

        $v->setValue(['x', null, 123]);
        $this->assertSame('x, , 123', (string) $v);

        $v->setValue([new \stdClass()]);
        $this->assertSame('', (string) $v);
    }
}
