<?php

namespace Shrd\Laravel\Azure\Storage\Tests\Extensions;

use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;
use Shrd\Laravel\Azure\Storage\Tables\Table;

trait InitializesStorageTables
{

    /**
     * @var array<string, Table>
     */
    protected array $testTables = [];

    public function tearDownCreatesStorageTables(): void
    {
        foreach ($this->testTables as $table) {
            try {
                $table->delete();
            } catch (AzureStorageServiceException) {}
        }
        $this->testTables = [];
    }

    public function makeTestTable(?string $name = null): Table
    {
        $name ??= $this->fake()->bothify('TestTable????????????');

        return new Table($this->makeStorageAccount()->table(), $name);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function createTestTable(?string $name = null): Table
    {
        $testTable = $this->makeTestTable($name);
        $testTable->ensureExists();
        $this->testTables[] = $testTable;
        return $testTable;
    }
}
