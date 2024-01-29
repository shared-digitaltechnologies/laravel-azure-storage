<?php

namespace Shrd\Laravel\Azure\Storage;

use ArrayAccess;
use ArrayIterator;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Arr;
use IteratorAggregate;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use RuntimeException;
use Shrd\Laravel\Azure\Identity\AzureCredentialService;
use Shrd\Laravel\Azure\KeyVault\KeyVaultService;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\Authentication\AzureStorageConnectionString;
use Traversable;

/**
 * @implements ArrayAccess<string, StorageAccount>
 * @implements IteratorAggregate<string, StorageAccount>
 */
class AzureStorageService implements ArrayAccess, IteratorAggregate
{

    private const KEY_VAULT_RESOLVABLE_CONFIG_KEYS = [
        'connection_string',
        'account_key',
    ];

    protected ?string $defaultKey = null;

    /**
     * @var array<StorageAccount> $storageAccounts
     */
    protected array $storageAccounts = [];

    protected array $storageAccountConfigs;

    protected array $resolvedStorageAccountConfigs = [];

    public function __construct(protected ConfigRepository $config,
                                protected AzureCredentialService $credentialService,
                                protected KeyVaultService $keyVaultService)
    {
        $this->storageAccountConfigs = $this->config->get('azure-storage.connections', []);
    }

    public function getDefaultKey(): string
    {
        if($this->defaultKey === null) {
            $this->defaultKey = $this->config->get('azure-storage.connection')
                ?? array_key_first($this->config->get('azure-storage.connections', []))
                ?? 'azure';
        }

        return $this->defaultKey;
    }

    public function setDefaultKey(string $key): static
    {
        $this->defaultKey = $key;
        return $this;
    }

    protected function getAccountConfigs(): array
    {
        return $this->storageAccountConfigs;
    }

    protected function getAccountConfig(?string $key = null): array|string
    {
        $key ??= $this->getDefaultKey();

        if(array_key_exists($key, $this->storageAccountConfigs)) {
            if($this->resolvedStorageAccountConfigs[$key] ?? false) {
                $result = $this->keyVaultService->resolveKeys(
                    $this->storageAccountConfigs[$key],
                    self::KEY_VAULT_RESOLVABLE_CONFIG_KEYS
                );
                $this->resolvedStorageAccountConfigs[$key] = true;
                return $result;
            } else {
                return $this->storageAccountConfigs[$key];
            }
        }

        throw new RuntimeException("Azure StorageConnection $key does not exist");
    }

    public function registerStorageAccount(string $key, $account = []): static
    {
        if(!($account instanceof StorageAccount)) {
            $account = $this->createAccount($account);
        }
        $this->storageAccounts[$key] = $account;
        return $this;
    }

    public function registerStorageAccounts(iterable $accounts): static
    {
        foreach ($accounts as $key => $account) {
            $this->registerStorageAccount($key, $account);
        }
        return $this;
    }

    public function createAccount($account = []): StorageAccount
    {
        if($account instanceof StorageAccount) return $account;

        if(is_string($account)) {
            $account = AzureStorageConnectionString::fromString($account);
        }

        if($account instanceof AzureStorageConnectionString) {
            return StorageAccount::fromConnectionString(
                connectionString: $account,
                credential: $this->credentialService->credential()
            );
        }

        if(is_array($account)) {
            $connectionString = AzureStorageConnectionString::fromConfig($account);
            $retryConfig = Arr::get($account, 'retry');
            $blobPublicEndpoint = Arr::get($account, 'blob.public_endpoint');
            $blobOptions = Arr::get($account, 'blob.options', []);
            $tableOptions = Arr::get($account, 'table.options', []);
            $queueOptions = Arr::get($account, 'queue.options', []);
            return StorageAccount::fromConnectionString(
                connectionString: $connectionString,
                credential: $this->credentialService->credential(Arr::get($account, 'credential_driver')),
                blobPublicEndpoint: $blobPublicEndpoint,
                retryConfig: $retryConfig,
                blobOptions: $blobOptions,
                queueOptions: $queueOptions,
                tableOptions: $tableOptions,
            );
        }

        throw new RuntimeException("Could not create StorageAccount from ".get_debug_type($account));
    }

    public function connectionString(?string $key = null): AzureStorageConnectionString
    {
        return AzureStorageConnectionString::fromConfig($this->getAccountConfig($key));
    }

    public function serviceSettings(?string $key = null): StorageServiceSettings
    {
        return $this->account($key)->serviceSettings();
    }

    public function account(?string $key = null): StorageAccount
    {
        $key ??= $this->getDefaultKey();
        if(array_key_exists($key, $this->storageAccounts)) {
            return $this->storageAccounts[$key];
        }

        $account = $this->createAccount($this->getAccountConfig($key));
        $this->storageAccounts[$key] = $account;
        return $account;
    }

    /**
     * @return array<string, StorageAccount>
     */
    public function accounts(): array
    {
        $result = [];
        foreach ($this->keys() as $key) {
            $result[$key] = $this->account($key);
        }
        return $result;
    }

    protected function keysFromConfig(): array
    {
        return array_keys($this->storageAccountConfigs);
    }

    public function keys(): array
    {
        return array_unique(array_merge($this->keysFromConfig(), array_keys($this->storageAccounts)));
    }

    public function hasAccount(?string $key = null): bool
    {
        $key ??= $this->getDefaultKey();

        return array_key_exists($key, $this->storageAccounts)
            || array_key_exists($key, $this->getAccountConfigs());
    }

    public function blob(?string $key = null): Blobs\BlobService
    {
        return $this->account($key)->blob();
    }

    public function table(?string $key = null): Tables\TableConnection
    {
        return $this->account($key)->table();
    }

    public function queue(?string $key = null): Queues\QueueService
    {
        return $this->account($key)->queue();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->accounts());
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->hasAccount($offset);
    }

    public function offsetGet(mixed $offset): StorageAccount
    {
        return $this->account($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->registerStorageAccount($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->storageAccounts[$offset]);
    }
}
