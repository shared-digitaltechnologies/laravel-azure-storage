<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use MicrosoftAzure\Storage\Table\Models\Filters\Filter;
use MicrosoftAzure\Storage\Table\Models\QueryEntitiesOptions;
use MicrosoftAzure\Storage\Table\Models\QueryEntitiesResult;
use RuntimeException;
use Safe\Exceptions\UrlException;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;
use Shrd\Laravel\Azure\Storage\Tables\Concerns\HasCursor;
use Shrd\Laravel\Azure\Storage\Tables\Concerns\MakesEntities;
use UnexpectedValueException;

/**
 * @template T of Entity
 */
class Builder extends QueryEntitiesOptions
{
    use HasCursor;
    use MakesEntities;

    /**
     * @param TableConnection $connection
     * @param string $table
     * @param class-string<T> $entityClass
     */
    public function __construct(protected TableConnection $connection,
                                protected string $table,
                                protected string $entityClass = Entity::class)
    {
        parent::__construct();
        $this->getQuery()->setTop(50);
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getConnection(): TableConnection
    {
        return $this->connection;
    }

    public function getLimit(): int
    {
        return $this->getTop();
    }

    /**
     * @throws UrlException
     */
    public function after($cursor): static
    {
        if($cursor instanceof \MicrosoftAzure\Storage\Table\Models\Entity) {
            $this->setNextPartitionKey($cursor->getPartitionKey());
            $this->setNextRowKey($cursor->getRowKey());
            return $this;
        }
        return $this->setCursor($cursor);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getRaw(string|array ...$fields): QueryEntitiesResult
    {
        $this->addSelect(...$fields);
        return $this->connection->queryEntities($this->table, $this);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getPage(string|array ...$fields): Result\Page
    {
        return new Result\Page($this, $this->getRaw(...$fields));
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function get(string|array ...$fields): Result
    {
        return new Result([$this->getPage(...$fields)]);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getAll(string|array ...$fields): array
    {
        return $this->get(...$fields)->all();
    }

    /**
     * @param string|mixed ...$fields
     * @return T|null
     * @throws AzureStorageServiceException
     */
    public function first(string|array ...$fields): ?Entity
    {
        $result = $this->getRaw(...$fields);
        $entities = $result->getEntities();
        return $this->makeEntity($entities[0] ?? null);
    }

    /**
     * @param string|mixed ...$fields
     * @return T
     * @throws AzureStorageServiceException
     */
    public function firstOrFail(string|array ...$fields): Entity
    {
        $entity = $this->first($fields);
        if(!$entity) throw new RuntimeException("Empty result", 404);
        return $entity;
    }

    public function select(string|array ...$fields): static
    {
        $this->setSelectFields(collect($fields)->flatten()->all());
        return $this;
    }

    public function addSelect(string|array ...$fields): static
    {
        $newFields = collect($this->getSelectFields())->concat(collect($fields)->flatten()->all())->all();
        $this->setSelectFields($newFields);
        return $this;
    }

    public function limit(int $number): static
    {
        $this->setTop($number);
        return $this;
    }

    public function setFilter(?Filter $filter = null): static
    {
        parent::setFilter($filter);
        return $this;
    }

    protected function addFilter(Filter $filter, bool $boolean = true, bool $or = false): static
    {
        if(!$boolean) $filter = Filter::applyNot($filter);

        $left = $this->getFilter();
        if(!$left) {
            $this->setFilter($filter);
        } else if($or) {
            $this->setFilter(Filter::applyOr($left, $filter));
        } else {
            $this->setFilter(Filter::applyAnd($left, $filter));
        }
        return $this;
    }

    protected function makeCompareFilter(Filter $lhs, string $operand, Filter $rhs): Filter
    {
        return match ($operand) {
            '<', 'lt' => Filter::applyLt($lhs, $rhs),
            '<=', 'le' => Filter::applyLe($lhs, $rhs),
            '>', 'gt' => Filter::applyGt($lhs, $rhs),
            '>=', 'ge' => Filter::applyGe($lhs, $rhs),
            '<>', '!=', '!==', 'ne' => Filter::applyNe($lhs, $rhs),
            '=', '==', '===', 'eq' => Filter::applyEq($lhs, $rhs),
            default => throw new UnexpectedValueException("Unknown operand '$operand'."),
        };
    }

    protected function makeConstant($value, ?string $edmType = null): Filter
    {
        return Filter::applyConstant($value, $edmType);
    }

    protected function makeCompareValueFilter(string $property,
                                              string $operand,
                                                     $value,
                                              ?EdmType $edmType = null): Filter
    {
        return $this->makeCompareFilter(
            lhs: Filter::applyPropertyName($property),
            operand: $operand,
            rhs: $this->makeConstant($value, $edmType?->value)
        );
    }

    public function whereIn(string $property, iterable $values, bool $boolean = true, bool $or = false): static
    {
        $filter = null;
        foreach ($values as $value) {
            $nextFilter = $this->makeCompareValueFilter($property, '=', $value);
            if($filter === null) {
                $filter = $nextFilter;
            } else {
                $filter = Filter::applyOr($filter, $nextFilter);
            }
        }

        if($filter === null) return $this;

        return $this->addFilter($filter, $boolean, $or);
    }

    public function where(string|array|callable $propertyOrValues,
                          ?string $operand = null,
                                                $value = null,
                          bool $boolean = true,
                          bool $or = false): static
    {
        if(is_callable($propertyOrValues)) {
            $subQuery = new self($this->connection, $this->table);
            call_user_func($propertyOrValues, $subQuery);
            return $this->addFilter($subQuery->getFilter(), $boolean, $or);
        }

        if(is_array($propertyOrValues)) {
            if(empty($propertyOrValues)) {
                return $this;
            }
            $filter = null;
            foreach ($propertyOrValues as $property => $value) {
                $nextFilter = $this->makeCompareValueFilter($property, '=', $value);
                if($filter === null) {
                    $filter = $nextFilter;
                } else {
                    $filter = Filter::applyAnd($filter, $nextFilter);
                }
            }
            return $this->addFilter($filter, $boolean, $or);
        }

        return $this->addFilter($this->makeCompareValueFilter($propertyOrValues, $operand, $value), $boolean, $or);
    }

    public function orWhere(string|array|callable $propertyOrValues,
                            ?string $operand = null,
                                                  $value = null,
                            bool $boolean = true): static
    {
        return $this->where($propertyOrValues, $operand, $value, $boolean, true);
    }

    public function notWhere(string|array|callable $propertyOrValues,
                             ?string $operand = null,
                                                   $value = null,
                             bool $or = false): static
    {
        return $this->where($propertyOrValues, $operand, $value, false, $or);
    }

    public function orNotWhere(string|array|callable $propertyOrValues,
                               ?string $operand = null,
                                                     $value = null): static
    {
        return $this->where($propertyOrValues, $operand, $value, false, true);
    }

    public function whereRaw(string $filter,
                             bool $boolean = true,
                             bool $or = false): static
    {
        return $this->addFilter(Filter::applyQueryString($filter), $boolean, $or);
    }

    public function orWhereRaw(string $filter,
                               bool $boolean = true): static
    {
        return $this->whereRaw($filter, $boolean, true);
    }

    public function notWhereRaw(string $filter,
                                bool $or = false): static
    {
        return $this->whereRaw($filter, false, $or);
    }
}
