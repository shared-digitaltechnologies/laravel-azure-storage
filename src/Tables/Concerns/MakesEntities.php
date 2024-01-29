<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Concerns;

use Shrd\Laravel\Azure\Storage\Tables\Entity;

/**
 * @template T of Entity
 */
trait MakesEntities
{
    /**
     * @return class-string<T>
     */
    public abstract function getEntityClass(): string;

    /**
     * @param $entity
     * @return T|null
     */
    protected function makeEntity($entity): ?Entity
    {
        $className = $this->getEntityClass();
        return $className::coerce($entity);
    }

    /**
     * @param $entity
     * @return T
     */
    protected function initEntity($entity): Entity
    {
        $className = $this->getEntityClass();
        return $className::from($entity);
    }


}
