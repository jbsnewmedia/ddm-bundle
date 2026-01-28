<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMUniqueValidator extends DDMValidator
{
    protected int $priority = self::DEFAULT_PRIORITY;

    public function validate(mixed $value): bool
    {
        // Unique check usually requires database access and entity context.
        // For now, we provide the validator class with the correct priority.
        // The actual validation logic would be implemented or injected here.
        return true;
    }
}
