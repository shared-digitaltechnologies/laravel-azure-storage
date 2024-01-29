<?php

namespace Shrd\Laravel\Azure\Storage\Facades;

use Illuminate\Support\Facades\Facade;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\Authentication\AzureStorageConnectionString;
use Shrd\Laravel\Azure\Storage\AzureStorageService;
use Shrd\Laravel\Azure\Storage\Blobs\BlobService;
use Shrd\Laravel\Azure\Storage\Queues\QueueService;
use Shrd\Laravel\Azure\Storage\Tables\TableConnection;

/**
 * @method static string getDefaultKey()
 * @method static AzureStorageService setDefaultKey(string $key)
 * @method static AzureStorageService registerStorageAccount(string $key, $account = [])
 * @method static AzureStorageService registerStorageAccounts(iterable $accounts)
 * @method static StorageAccount createAccount($account = [])
 * @method static AzureStorageConnectionString connectionString(?string $key = null)
 * @method static StorageServiceSettings serviceSettings(?string $key = null)
 * @method static StorageAccount account(?string $key = null)
 * @method static array<string, StorageAccount> accounts()
 * @method static array<int, string> keys()
 * @method static bool hasAccount(?string $key = null)
 * @method static BlobService blob(?string $key = null)
 * @method static TableConnection table(?string $key = null)
 * @method static QueueService queue(?string $key = null)
 */
class AzureStorage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AzureStorageService::class;
    }
}
