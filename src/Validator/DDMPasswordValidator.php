<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Validator;

class DDMPasswordValidator extends DDMValidator
{
    private int $minLength = 8;
    private bool $requireLowercase = true;
    private bool $requireUppercase = true;
    private bool $requireNumbers = true;
    private bool $requireSpecialChars = true;

    public function __construct()
    {
        $this->alias = 'password';
    }

    public function setMinLength(int $minLength): self
    {
        $this->minLength = $minLength;
        return $this;
    }

    public function setRequireLowercase(bool $requireLowercase): self
    {
        $this->requireLowercase = $requireLowercase;
        return $this;
    }

    public function setRequireUppercase(bool $requireUppercase): self
    {
        $this->requireUppercase = $requireUppercase;
        return $this;
    }

    public function setRequireNumbers(bool $requireNumbers): self
    {
        $this->requireNumbers = $requireNumbers;
        return $this;
    }

    public function setRequireSpecialChars(bool $requireSpecialChars): self
    {
        $this->requireSpecialChars = $requireSpecialChars;
        return $this;
    }

    public function validate(mixed $value): bool
    {
        $password = $value;
        if (is_array($value)) {
            $password = $value[0] ?? '';
        }

        if (null === $password || '' === trim((string) $password)) {
            return true;
        }

        $password = trim((string) $password);

        if (mb_strlen($password) < $this->minLength) {
            $this->setErrorMessage('password.too_short');
            $this->setErrorMessageParameters(['{min_length}' => (string) $this->minLength]);
            return false;
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/u', $password)) {
            $this->setErrorMessage('password.require_lowercase');
            return false;
        }

        if ($this->requireUppercase && !preg_match('/[A-Z]/u', $password)) {
            $this->setErrorMessage('password.require_uppercase');
            return false;
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/u', $password)) {
            $this->setErrorMessage('password.require_numbers');
            return false;
        }

        if ($this->requireSpecialChars && !preg_match('/[^a-zA-Z0-9äöüÄÖÜ]/u', $password)) {
            $this->setErrorMessage('password.require_special_chars');
            return false;
        }

        // Match check: only when field has 2 inputs
        if (is_array($value) && count($value) >= 2) {
            $first = $value[0] ?? '';
            $second = $value[1] ?? '';

            if (('' === trim((string) $first)) xor ('' === trim((string) $second))) {
                $this->setErrorMessage('password.match_error');
                return false;
            }

            if ($first !== $second) {
                $this->setErrorMessage('password.match_error');
                return false;
            }
        }

        return true;
    }
}
