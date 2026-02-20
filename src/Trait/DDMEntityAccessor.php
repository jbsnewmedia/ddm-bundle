<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Trait;

/**
 * Provides helper methods for reading/writing entity properties via getter/setter convention.
 *
 * Used by DDMField and DDMDatatableFormHandler to avoid duplicating
 * the `'get' . ucfirst($identifier)` pattern.
 */
trait DDMEntityAccessor
{
    /**
     * Returns the value of an entity property via its getter method, or null if not found.
     */
    protected function getEntityValue(object $entity, string $identifier): mixed
    {
        $method = 'get'.ucfirst($identifier);
        if (method_exists($entity, $method)) {
            return $entity->$method();
        }

        return null;
    }

    /**
     * Sets a value on an entity via its setter method.
     * Returns true if the setter was called, false if the method does not exist.
     */
    protected function setEntityValue(object $entity, string $identifier, mixed $value): bool
    {
        $method = 'set'.ucfirst($identifier);
        if (method_exists($entity, $method)) {
            $entity->$method($value);

            return true;
        }

        return false;
    }
}
