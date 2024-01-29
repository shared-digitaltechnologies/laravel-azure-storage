<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use MicrosoftAzure\Storage\Table\Internal\ITable;
use MicrosoftAzure\Storage\Table\TableSharedAccessSignatureHelper;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\Tables\Concerns\ManagesTableEntities;
use Shrd\Laravel\Azure\Storage\Tables\Concerns\ManagesTables;
use Shrd\Laravel\Azure\Storage\Tables\Concerns\ValidatesTableNames;

class TableConnection
{
    use ValidatesTableNames, ManagesTables, ManagesTableEntities;

    public function __construct(protected StorageAccount $storageAccount,
                                protected ITable $proxy)
    {
    }

    public function getStorageAccount(): StorageAccount
    {
        return $this->storageAccount;
    }

    public function sasHelper(): TableSharedAccessSignatureHelper
    {
        return new TableSharedAccessSignatureHelper(
            $this->storageAccount->getName(),
            $this->storageAccount->getKey(),
        );
    }

    public function getProxy(): ITable
    {
        return $this->proxy;
    }

    public function query(string $table, string $entityClass = Entity::class): Builder
    {
        return new Builder($this, $table, $entityClass);
    }

    public function batch(string $table): Batch
    {
        return new Batch($this, $table);
    }
}
