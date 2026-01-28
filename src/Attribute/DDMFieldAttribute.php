<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class DDMFieldAttribute
{
    public function __construct(
        public ?string $entity = null,
        public ?string $identifier = null,
        public ?string $setTo = null,
        public int $order = 100,
    ) {
    }
}
