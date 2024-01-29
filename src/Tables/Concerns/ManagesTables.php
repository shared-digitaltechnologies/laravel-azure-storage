<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Concerns;

use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Table\Models\Filters\Filter;
use MicrosoftAzure\Storage\Table\Models\GetTableResult;
use MicrosoftAzure\Storage\Table\Models\QueryTablesOptions;
use MicrosoftAzure\Storage\Table\Models\QueryTablesResult;
use MicrosoftAzure\Storage\Table\Models\TableACL;
use MicrosoftAzure\Storage\Table\Models\TableServiceCreateOptions;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;
use Shrd\Laravel\Azure\Storage\Tables\EdmType;
use Shrd\Laravel\Azure\Storage\Tables\Table;

trait ManagesTables
{
    /**
     * @throws AzureStorageServiceException
     */
    public function createTable(string $table, bool $doesReturnContent = false): Table
    {
        $options = new TableServiceCreateOptions();
        $options->setDoesReturnContent($doesReturnContent);
        try {
            $this->proxy->createTable($table, $options);
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }

        return new Table($this, $table);
    }

    /**
     * Idempotent version of `createTable`.
     *
     * @param string $table
     * @param bool $doesReturnContent
     * @return Table
     * @throws AzureStorageServiceException
     */
    public function ensureTableExists(string $table, bool $doesReturnContent = false): Table
    {
        try {
            $this->createTable($table, $doesReturnContent);
            return new Table($this, $table);
        } catch (AzureStorageServiceException $exception) {
            if($exception->hasErrorCode('TableAlreadyExists')) return new Table($this, $table);
            throw $exception;
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function deleteTable(string $table): static
    {
        try {
            $this->proxy->deleteTable($table);
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->hasErrorCode('TableNotFound')) return $this;
            throw $exception;
        }

        return $this;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getTable(string $table): GetTableResult
    {
        try {
            return $this->proxy->getTable($table);
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getTables(): array
    {
        try {
            return $this->proxy->queryTables()->getTables();
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function tableExists(string $table): bool
    {
        $options = new QueryTablesOptions();
        $options->setFilter(Filter::applyEq(Filter::applyPropertyName('TableName'), Filter::applyConstant($table, EdmType::STRING->value)));
        return count($this->queryTables($options)->getTables()) > 0;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getTableAcl(string $table): TableACL
    {
        try {
            return $this->proxy->getTableAcl($table);
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function setTableAcl(string $table, TableACL $tableACL): static
    {
        try {
            $this->proxy->setTableAcl($table, $tableACL);
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->hasErrorCode('TableNotFound')) {
                $this->ensureTableExists($table);
                $this->setTableAcl($table, $tableACL);
                return $this;
            }
            throw $exception;
        }
        return $this;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function queryTables(?QueryTablesOptions $options = null): QueryTablesResult
    {
        try {
            return $this->proxy->queryTables($options);
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }
}
