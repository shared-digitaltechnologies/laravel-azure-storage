<?php

namespace Shrd\Laravel\Azure\Storage\Tests;

use Faker;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use MicrosoftAzure\Storage\Table\TableRestProxy;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\Authentication\AzureStorageConnectionString;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            'Shrd\Laravel\Azure\Storage\ServiceProvider'
        ];
    }

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (ConfigRepository $config) {
            $config->set('azure-storage.connection', 'azure');
            $config->set('azure-storage.connections', [
                'azure' => [
                    'connection_string' => $this->getConnectionString()
                ]
            ]);
        });
    }

    protected function makeStorageAccount(): StorageAccount
    {
        return StorageAccount::fromConnectionString($this->getConnectionString());
    }

    protected function makeTableRestProxy(): TableRestProxy
    {
        return TableRestProxy::createTableService($this->getConnectionString());
    }

    protected function fake(): Faker\Generator
    {
        return Faker\Factory::create();
    }

    protected function getConnectionString(): string
    {
        return $_ENV['STORAGECONNSTR_StorageConnectionString']
            ?? $_ENV['AZURE_STORAGE_CONNECTION_STRING']
            ?? AzureStorageConnectionString::DEVELOPMENT_STORAGE;
    }
}
