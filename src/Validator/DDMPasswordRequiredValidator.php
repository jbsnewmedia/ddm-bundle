<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMPasswordRequiredValidator extends DDMValidator
{
    public function __construct()
    {
        $this->alias = 'required';
    }

    public function validate(mixed $value): bool
    {
        $field = $this->getField();
        $isEditMode = $field && $field->getDdm() && $field->getDdm()->getEntityId();

        $first = '';
        $second = '';
        if (is_array($value)) {
            $first = array_key_exists(0, $value) && is_string($value[0]) ? trim($value[0]) : '';
            $second = array_key_exists(1, $value) && is_string($value[1]) ? trim($value[1]) : '';
        } elseif (is_string($value)) {
            $first = trim($value);
        }

        $bothEmpty = '' === $first && '' === $second;

        if ($isEditMode && $bothEmpty) {
            return true;
        }

        if ($bothEmpty) {
            $this->setErrorMessage('required');

            return false;
        }

        return true;
    }

    public function isRequired(): bool
    {
        $field = $this->getField();
        $isEditMode = $field && $field->getDdm() && $field->getDdm()->getEntityId();

        return !$isEditMode;
    }
}
