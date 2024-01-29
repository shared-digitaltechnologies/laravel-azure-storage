<?php

namespace Shrd\Laravel\Azure\Storage\Tests\Feature;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\AzureStorageService;
use Shrd\Laravel\Azure\Storage\Tests\TestCase;

class AzureStorageServiceTest extends TestCase
{
    public function test_create_account_from_config()
    {
        /** @var AzureStorageService $service */
        $service = $this->app->make(AzureStorageService::class);

        $account = $service->createAccount(['connection_string' => 'UseDevelopmentStorage=true']);
        $this->assertInstanceOf(StorageAccount::class, $account);
    }

    public function test_gets_connection_name_from_config()
    {
        $connectionName = 'my_strange_connection_name';

        tap($this->app['config'], function (ConfigRepository $config) use ($connectionName) {
            $config->set('azure-storage', [
                'connection' => $connectionName
            ]);
        });

        /** @var AzureStorageService $service */
        $service = $this->app->make(AzureStorageService::class);

        $this->assertEquals($connectionName, $service->getDefaultKey());
    }

    public function test_gets_account_from_config()
    {
        $connectionName = 'abc';
        $accountName = 'Abc';

        tap($this->app['config'], function (ConfigRepository $config) use ($connectionName, $accountName) {
            $config->set('azure-storage', [
                'connection' => 'default',
                'connections' => [
                    $connectionName => [
                        'connection_string' => 'AccountName='.$accountName.';AccountKey=ABC;DefaultEndpointsProtocol=https'
                    ]
                ]
            ]);
        });

        /** @var AzureStorageService $service */
        $service = $this->app->make(AzureStorageService::class);

        $account = $service->account($connectionName);
        $this->assertInstanceOf(StorageAccount::class, $account);

        $this->assertEquals($accountName, $account->getName());
    }
}
