<?php

namespace Shrd\Laravel\Azure\Storage\Tests\Feature\Tables;

use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;
use Shrd\Laravel\Azure\Storage\Tests\Extensions\InitializesStorageTables;
use Shrd\Laravel\Azure\Storage\Tests\Extensions\MakesFakeEntities;
use Shrd\Laravel\Azure\Storage\Tests\TestCase;

class TableConnectionTest extends TestCase
{
    use InitializesStorageTables;
    use MakesFakeEntities;

    /**
     * @throws AzureStorageServiceException
     */
    public function test_insert_entity_creates_table_if_it_does_not_exist_yet()
    {
        $tableName = $this->fake()->bothify('Test???????');

        $connection = $this->makeStorageAccount()->table();

        $this->assertFalse($connection->tableExists($tableName));

        try {
            $connection->insertEntity($tableName, [
                'PartitionKey' => 'AAAA',
                'RowKey' => 'AAAA'
            ]);

            $this->assertTrue($connection->tableExists($tableName));
        } finally {
            $connection->deleteTable($tableName);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function test_ensureTableExists_is_idempotent()
    {
        $tableName = $this->fake()->bothify('Test????????');

        $connection = $this->makeStorageAccount()->table();

        $this->assertFalse($connection->tableExists($tableName));

        try {
            $this->assertFalse($connection->tableExists($tableName));

            $connection->ensureTableExists($tableName);
            $this->assertTrue($connection->tableExists($tableName));

            $connection->ensureTableExists($tableName);
            $this->assertTrue($connection->tableExists($tableName));
        } finally {
            $connection->deleteTable($tableName);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function test_can_insert_and_retrieve_an_entity()
    {
        $entity = $this->makeFakeEntity();

        $table = $this->createTestTable();
        $connection = $table->getConnection();

        $connection->insertEntity($table->getName(), $entity);

        $retrievedEntity = $connection->getEntity($table->getName(), $entity->getPartitionKey(), $entity->getRowKey());

        $this->assertEquals($entity->row_key, $retrievedEntity->row_key);
        $this->assertEquals($entity->partition_key, $retrievedEntity->partition_key);
        $this->assertEquals($entity['TestNumber'], $retrievedEntity['TestNumber']);
        $this->assertEquals($entity['TestDouble'], $retrievedEntity['TestDouble']);
        $this->assertEquals($entity['TestBoolean'], $retrievedEntity['TestBoolean']);
        $this->assertEquals($entity['TestText'], $retrievedEntity['TestText']);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function test_can_retrieve_one_entity_from_many()
    {
        $entity = $this->makeFakeEntity();

        $table = $this->createTestTable();
        $connection = $table->getConnection();

        for($i = 0; $i < 20; $i++) {
            $connection->insertEntity($table->getName(), $this->makeFakeEntity());
        }

        $connection->insertEntity($table->getName(), $entity);

        for($i = 0; $i < 20; $i++) {
            $connection->insertEntity($table->getName(), $this->makeFakeEntity());
        }

        $retrievedEntity = $connection->getEntity($table->getName(), $entity->getPartitionKey(), $entity->getRowKey());

        $this->assertEquals($entity->row_key, $retrievedEntity->row_key);
        $this->assertEquals($entity->partition_key, $retrievedEntity->partition_key);
        $this->assertEquals($entity['TestNumber'], $retrievedEntity['TestNumber']);
        $this->assertEquals($entity['TestDouble'], $retrievedEntity['TestDouble']);
        $this->assertEquals($entity['TestBoolean'], $retrievedEntity['TestBoolean']);
        $this->assertEquals($entity['TestText'], $retrievedEntity['TestText']);
    }
}
