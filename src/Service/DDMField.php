<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use JBSNewMedia\DDMBundle\Validator\DDMValidator;

abstract class DDMField
{
    public const DEFAULT_PRIORITY = 100;

    protected string $identifier;
    protected string $name;
    protected string $type;
    protected ?string $value = null;
    protected int $order = 100;
    protected bool $livesearch = true;
    protected bool $extendsearch = true;
    protected bool $sortable = true;
    protected string $template = '@DDM/fields/text.html.twig';
    /** @var DDMValidator[] */
    protected array $validators = [];
    protected array $errors = [];

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function isLivesearch(): bool
    {
        return $this->livesearch;
    }

    public function setLivesearch(bool $livesearch): self
    {
        $this->livesearch = $livesearch;
        return $this;
    }

    public function isExtendsearch(): bool
    {
        return $this->extendsearch;
    }

    public function setExtendsearch(bool $extendsearch): self
    {
        $this->extendsearch = $extendsearch;
        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $sortable): self
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function addValidator(DDMValidator $validator): self
    {
        if ($validator->getAlias()) {
            $this->removeValidator($validator->getAlias());
        }
        $this->validators[] = $validator;
        usort($this->validators, function (DDMValidator $a, DDMValidator $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
        return $this;
    }

    public function removeValidator(string $alias): self
    {
        $this->validators = array_filter($this->validators, function (DDMValidator $validator) use ($alias) {
            return $validator->getAlias() !== $alias;
        });
        return $this;
    }

    /** @return DDMValidator[] */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function isRequired(): bool
    {
        foreach ($this->validators as $validator) {
            if ($validator->isRequired()) {
                return true;
            }
        }
        return false;
    }

    public function validate(mixed $value): bool
    {
        $this->errors = [];
        foreach ($this->validators as $validator) {
            if (!$validator->validate($value)) {
                $this->errors[] = $validator->getErrorMessage();
                return false;
            }
        }
        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}
