<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use JBSNewMedia\DDMBundle\Value\DDMArrayValue;

/**
 * Base class for fields that manage a OneToMany relation of option rows
 * (e.g. user-to-client or client-to-tool assignments) rendered as a list
 * of checkboxes and displayed as a comma separated label list.
 *
 * Subclasses define the available options and how relation rows map to
 * option ids; this base class implements value preparation, datatable and
 * form rendering as well as livesearch filtering via a LEFT JOIN.
 */
abstract class DDMRelationField extends DDMField
{
    protected string $emptyPlaceholder = '-';

    public function __construct()
    {
        $this->setTemplate('@DDM/fields/choices.html.twig');
        $this->setSortable(false);
        $this->setLivesearch(true);

        $this->setValueHandler(new DDMArrayValue());
        $this->getValueHandler()->setType('text');
    }

    /**
     * All selectable options as option id => label.
     *
     * @return array<string, string>
     */
    abstract public function getAvailableChoices(): array;

    /**
     * The current relation rows of the entity.
     *
     * @return iterable<int, object>
     */
    abstract public function getRelationCollection(object $entity): iterable;

    /**
     * The option id a single relation row points to, or null to skip it.
     */
    abstract public function getRelationId(object $relation): ?string;

    /**
     * The OneToMany property on the entity used to join for livesearch.
     */
    abstract public function getSearchJoinProperty(): string;

    /**
     * The field on the relation row compared against the option ids.
     */
    abstract public function getSearchTargetField(): string;

    public function setEmptyPlaceholder(string $placeholder): void
    {
        $this->emptyPlaceholder = $placeholder;
    }

    public function getEmptyPlaceholder(): string
    {
        return $this->emptyPlaceholder;
    }

    /**
     * @return list<string>
     */
    public function prepareValue(mixed $value): array
    {
        if ($value instanceof Collection) {
            $selected = [];
            foreach ($value as $relation) {
                if (!\is_object($relation)) {
                    continue;
                }

                $id = $this->getRelationId($relation);
                if (null !== $id) {
                    $selected[] = (string) $id;
                }
            }

            return $selected;
        }

        if (\is_array($value)) {
            return array_values(array_filter($value, is_string(...)));
        }

        return [];
    }

    public function renderDatatable(object $entity): string
    {
        $labels = [];
        foreach ($this->prepareValue($this->getRelationCollection($entity)) as $id) {
            $labels[] = $this->getAvailableChoices()[$id] ?? $id;
        }

        if ([] === $labels) {
            return $this->emptyPlaceholder;
        }

        return implode(', ', $labels);
    }

    /**
     * @return list<string>
     */
    public function renderForm(object $entity): array
    {
        return $this->prepareValue($this->getRelationCollection($entity));
    }

    public function getSearchExpression(QueryBuilder $qb, string $alias, string $search): ?\Stringable
    {
        $ids = [];
        foreach ($this->getAvailableChoices() as $id => $label) {
            if (str_contains(strtolower($label), strtolower($search)) || str_contains(strtolower($id), strtolower($search))) {
                $ids[] = $id;
            }
        }

        if ([] === $ids) {
            return $qb->expr()->eq('1', '0');
        }

        $joinAlias = 'ddm_search_'.$this->getSearchJoinProperty();
        $hasJoin = false;
        $joinParts = $qb->getDQLPart('join');
        foreach (\is_array($joinParts) ? $joinParts : [] as $joinList) {
            if (!\is_array($joinList)) {
                continue;
            }
            foreach ($joinList as $join) {
                if ($join instanceof Join && $join->getAlias() === $joinAlias) {
                    $hasJoin = true;
                    break 2;
                }
            }
        }

        if (!$hasJoin) {
            $qb->leftJoin($alias.'.'.$this->getSearchJoinProperty(), $joinAlias);
        }

        return $qb->expr()->in($joinAlias.'.'.$this->getSearchTargetField(), $ids);
    }
}
