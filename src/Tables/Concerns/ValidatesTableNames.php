<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Concerns;

trait ValidatesTableNames
{
    public function isValidTableName(string $table): bool
    {
        return !!preg_match("/^[a-zA-Z][a-zA-Z0-9]{2,62}$/", $table);
    }

    public function assertValidTableName(string $table): bool
    {
        return assert($this->isValidTableName($table));
    }
}
