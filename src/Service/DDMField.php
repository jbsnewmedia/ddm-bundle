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
    protected bool $renderInForm = true;
    protected bool $renderInTable = true;
    protected string $template = '@DDM/fields/text.html.twig';
    /** @var DDMValidator[] */
    protected array $validators = [];
    /** @var string[] */
    protected array $errors = [];
    /** @var DDMField[] */
    protected array $subFields = [];

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /** @return DDMField[] */
    public function getSubFields(): array
    {
        return $this->subFields;
    }

    /** @param DDMField[] $subFields */
    public function setSubFields(array $subFields): self
    {
        $this->subFields = $subFields;

        return $this;
    }

    public function addSubField(DDMField $subField): self
    {
        $this->subFields[] = $subField;

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

    public function isRenderInForm(): bool
    {
        return $this->renderInForm;
    }

    public function setRenderInForm(bool $renderInForm): self
    {
        $this->renderInForm = $renderInForm;

        return $this;
    }

    public function isRenderInTable(): bool
    {
        return $this->renderInTable;
    }

    public function setRenderInTable(bool $renderInTable): self
    {
        $this->renderInTable = $renderInTable;

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
        usort($this->validators, fn (DDMValidator $a, DDMValidator $b) => $b->getPriority() <=> $a->getPriority());

        return $this;
    }

    public function removeValidator(string $alias): self
    {
        $this->validators = array_filter($this->validators, fn (DDMValidator $validator) => $validator->getAlias() !== $alias);

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
                $errorMessage = $validator->getErrorMessage();
                if (is_string($errorMessage)) {
                    $this->errors[] = $errorMessage;
                }

                return false;
            }
        }

        return true;
    }

    /** @return string[] */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getError(): ?string
    {
        $error = $this->errors[0] ?? null;

        return is_string($error) ? $error : null;
    }

    /** @return string|array<mixed> */
    public function render(object $entity): string|array
    {
        if (null !== $this->value) {
            return (string) $this->value;
        }

        $method = 'get'.ucfirst($this->identifier);
        if (method_exists($entity, $method)) {
            $result = $entity->$method();
            if (is_string($result) || is_array($result)) {
                return $result;
            }

            return is_scalar($result) || (is_object($result) && method_exists($result, '__toString')) ? (string) $result : '';
        }

        return '';
    }

    /**
     * @param iterable<DDMField> $allFields
     */
    public function init(iterable $allFields): void
    {
    }
}
