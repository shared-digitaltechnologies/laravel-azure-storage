<?php

namespace Shrd\Laravel\Azure\Storage\Facades;

use Illuminate\Support\Facades\Facade;
use MicrosoftAzure\Storage\Table\Internal\ITable;
use MicrosoftAzure\Storage\Table\Models\GetTableResult;
use MicrosoftAzure\Storage\Table\Models\QueryEntitiesOptions;
use MicrosoftAzure\Storage\Table\Models\QueryEntitiesResult;
use MicrosoftAzure\Storage\Table\Models\QueryTablesOptions;
use MicrosoftAzure\Storage\Table\Models\QueryTablesResult;
use MicrosoftAzure\Storage\Table\Models\TableACL;
use MicrosoftAzure\Storage\Table\TableSharedAccessSignatureHelper;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\Tables\Batch;
use Shrd\Laravel\Azure\Storage\Tables\Builder;
use Shrd\Laravel\Azure\Storage\Tables\Entity;
use Shrd\Laravel\Azure\Storage\Tables\Table;
use Shrd\Laravel\Azure\Storage\Tables\TableConnection;

/**
 * Accesses the storage table service of the default azure storage connection.
 *
 * @method static StorageAccount getStorageAccount()
 * @method static TableSharedAccessSignatureHelper sasHelper()
 * @method static ITable getProxy()
 * @method static Builder query(string $table, string $entityClass = Entity::class)
 * @method static Batch batch(string $table)
 * @method static bool isValidTableName(string $table)
 * @method static bool assertValidTableName(string $table)
 * @method static Table createTable(string $table, bool $doesReturnContent = false)
 * @method static Table ensureTableExists(string $table, bool $doesReturnContent = false)
 * @method static TableConnection deleteTable(string $table)
 * @method static GetTableResult getTable(string $table)
 * @method static array getTables()
 * @method static bool tableExists(string $table)
 * @method static TableACL getTableAcl(string $table)
 * @method static TableConnection setTableAcl(string $table, TableACL $tableACL)
 * @method static QueryTablesResult queryTables(?QueryTablesOptions $options = null)
 * @method static Entity getEntity(string $table, string $partitionKey, string $rowKey)
 * @method static Entity insertEntity(string $table, $entity)
 * @method static string saveEntity(string $table, $entity)
 * @method static string upsertEntity(string $table, $entity)
 * @method static string mergeEntity(string $table, $entity)
 * @method static string updateEntity(string $table, $entity)
 * @method static void deleteEntity(string $table, $entityOrPartitionKey, $rowKey = null)
 * @method static QueryEntitiesResult queryEntities(string $table, QueryEntitiesOptions|null $options = null)
 */
class StorageTable extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TableConnection::class;
    }
}
