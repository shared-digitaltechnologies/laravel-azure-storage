<?php

namespace Shrd\Laravel\Azure\Storage\Accounts;

use MicrosoftAzure\Storage\Blob\Internal\BlobResources;
use MicrosoftAzure\Storage\Common\Internal\Authentication\IAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Authentication\SharedAccessSignatureAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Authentication\SharedKeyAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Middlewares\CommonRequestMiddleware;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use MicrosoftAzure\Storage\Common\Middlewares\RetryMiddleware;
use MicrosoftAzure\Storage\Queue\Internal\QueueResources;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Table\Internal\Authentication\TableSharedKeyLiteAuthScheme;
use MicrosoftAzure\Storage\Table\Internal\JsonODataReaderWriter;
use MicrosoftAzure\Storage\Table\Internal\MimeReaderWriter;
use MicrosoftAzure\Storage\Table\Internal\TableResources;
use MicrosoftAzure\Storage\Table\TableRestProxy;
use RuntimeException;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Storage\Authentication\AzureStorageConnectionString;
use Shrd\Laravel\Azure\Storage\Authentication\CredentialAuthScheme;
use Shrd\Laravel\Azure\Storage\Blobs\BlobService;
use Shrd\Laravel\Azure\Storage\Files\FileService;
use Shrd\Laravel\Azure\Storage\Middleware\RetryMiddlewareFactory;
use Shrd\Laravel\Azure\Storage\Queues\QueueService;
use Shrd\Laravel\Azure\Storage\Tables\TableConnection;

class StorageAccount
{
    protected ?BlobService $blob = null;
    protected ?QueueService $queue = null;
    protected ?TableConnection $table = null;
    protected ?FileService $file = null;

    public function __construct(protected StorageServiceSettings $serviceSettings,
                                protected ?TokenCredential $credential,
                                protected ?string $blobPublicEndpoint,
                                protected ?array $retryConfig,
                                protected array $blobOptions = [],
                                protected array $queueOptions = [],
                                protected array $tableOptions = [],
                                protected array $fileOptions = [])
    {
    }

    public static function azurite(string $host = '17.0.0.1',
                                   ?string $accountName = null,
                                   ?string $accountKey = null): static
    {
        $connectionString = AzureStorageConnectionString::azurite($host, $accountName, $accountKey);
        return static::fromConnectionString($connectionString);
    }

    public static function fromConnectionString($connectionString = null,
                                                ?TokenCredential $credential = null,
                                                ?string $blobPublicEndpoint = null,
                                                ?array $retryConfig = null,
                                                array $blobOptions = [],
                                                array $queueOptions = [],
                                                array $tableOptions = []): self
    {
        if(is_array($connectionString)) {
            $connectionString = AzureStorageConnectionString::from($connectionString);
        } else if(is_string($connectionString)) {
            $connectionString = AzureStorageConnectionString::fromString($connectionString);
        } else if(!$connectionString instanceof AzureStorageConnectionString) {
            $connectionString = AzureStorageConnectionString::azurite();
        }

        return new self(
            $connectionString->getServiceSettings(),
            credential: $credential,
            blobPublicEndpoint: $blobPublicEndpoint,
            retryConfig: $retryConfig,
            blobOptions: $blobOptions,
            queueOptions: $queueOptions,
            tableOptions: $tableOptions
        );
    }

    public function getKey(): ?string
    {
        return $this->serviceSettings->getKey();
    }

    public function hasKey(): bool
    {
        return $this->getKey() !== null;
    }

    public function getSasToken(): ?string
    {
        return $this->serviceSettings->getSasToken();
    }

    public function hasSasToken(): bool
    {
        return $this->serviceSettings->hasSasToken();
    }

    public function getName(): ?string
    {
        return $this->serviceSettings->getName();
    }

    public function hasName(): ?string
    {
        return $this->serviceSettings->getName() !== null;
    }

    public function serviceSettings(): StorageServiceSettings
    {
        return  $this->serviceSettings;
    }

    protected function createAuthScheme(): IAuthScheme
    {
        if($this->hasName() && $this->hasKey()) {
            return $this->createKeyAuthScheme();
        }

        if($this->hasSasToken()) {
            return $this->createSasAuthScheme();
        }

        return $this->createCredentialAuthScheme();
    }

    protected function createKeyAuthScheme(): SharedKeyAuthScheme
    {
        return new SharedKeyAuthScheme(
            $this->serviceSettings->getName(),
            $this->serviceSettings->getKey()
        );
    }

    protected function createSasAuthScheme(): SharedAccessSignatureAuthScheme
    {
        return new SharedAccessSignatureAuthScheme($this->getSasToken());
    }

    protected function createCredentialAuthScheme(): CredentialAuthScheme
    {
        if(!$this->credential) {
            throw new RuntimeException("No Credential Service for StorageAccount");
        }

        return new CredentialAuthScheme($this->credential);
    }

    protected function createAuthMiddleware(string $storageAPIVersion,
                                            string $serviceSDKVersion,
                                            array $headers = []): CommonRequestMiddleware
    {
        return new CommonRequestMiddleware(
            authenticationScheme: $this->createAuthScheme(),
            storageAPIVersion: $storageAPIVersion,
            serviceSDKVersion: $serviceSDKVersion,
            headers: $headers,
        );
    }

    protected function createRetryMiddleware(): ?RetryMiddleware
    {
        if(!$this->retryConfig) return null;
        return RetryMiddlewareFactory::fromConfig($this->retryConfig);
    }


    public function getBlobPublicEndpoint(): string
    {
        return $this->blobPublicEndpoint ?? $this->serviceSettings->getBlobEndpointUri();
    }

    public function getBlobMiddleware(): array
    {
        $middleware = [$this->createAuthMiddleware(
            storageAPIVersion: BlobResources::STORAGE_API_LATEST_VERSION,
            serviceSDKVersion: BlobResources::BLOB_SDK_VERSION,
        )];

        $retry = $this->createRetryMiddleware();
        if($retry) $middleware[] = $retry;

        return $middleware;
    }

    public function blob(): BlobService
    {
        if(!$this->blob) {
            $this->blob = new BlobService(
                storageAccount: $this,
                middleware: $this->getBlobMiddleware(),
                options: $this->blobOptions,
            );
        }
        return $this->blob;
    }

    public function getQueueMiddleware(): array
    {
        $middleware = [$this->createAuthMiddleware(
            storageAPIVersion: QueueResources::STORAGE_API_LATEST_VERSION,
            serviceSDKVersion: QueueResources::QUEUE_SDK_VERSION,
        )];

        $retry = $this->createRetryMiddleware();
        if($retry) $middleware[] = $retry;

        return $middleware;
    }

    public function queue(): QueueService
    {
        if(!$this->queue) {
            $settings = $this->serviceSettings();

            $proxy = new QueueRestProxy(
                primaryUri: $settings->getQueueEndpointUri(),
                secondaryUri: $settings->getQueueSecondaryEndpointUri(),
                accountName: $settings->getName(),
                options: $this->queueOptions,
            );

            foreach ($this->getQueueMiddleware() as $middleware) {
                $proxy->pushMiddleware($middleware);
            }

            $this->queue = new QueueService(
                storageAccount: $this,
                proxy: $proxy,
            );
        }
        return $this->queue;
    }

    public function getTableMiddleware(): array
    {
        $middleware = [];

        if ($this->hasName() && $this->hasKey()) {
            $settings = $this->serviceSettings();
            $authScheme = new TableSharedKeyLiteAuthScheme(
                $settings->getName(),
                $settings->getKey()
            );
        } else if ($this->hasSasToken()) {
            $authScheme = $this->createSasAuthScheme();
        } else {
            $authScheme = $this->createCredentialAuthScheme();
        }

        $middleware[] = new CommonRequestMiddleware(
            authenticationScheme: $authScheme,
            storageAPIVersion: TableResources::STORAGE_API_LATEST_VERSION,
            serviceSDKVersion: TableResources::TABLE_SDK_VERSION,
            headers: [
                Resources::DATA_SERVICE_VERSION => TableResources::DATA_SERVICE_VERSION_VALUE,
                Resources::MAX_DATA_SERVICE_VERSION => TableResources::MAX_DATA_SERVICE_VERSION_VALUE,
                Resources::ACCEPT_HEADER => TableResources::ACCEPT_HEADER_VALUE,
                Resources::ACCEPT_CHARSET => TableResources::ACCEPT_CHARSET_VALUE,
            ],
        );

        $retry = $this->createRetryMiddleware();
        if($retry) $middleware[] = $retry;

        return $middleware;
    }

    public function table(): TableConnection
    {
        if(!$this->table) {
            $settings = $this->serviceSettings();

            $proxy = new TableRestProxy(
                primaryUri: $settings->getTableEndpointUri(),
                secondaryUri: $settings->getTableSecondaryEndpointUri(),
                odataSerializer: new JsonODataReaderWriter(),
                mimeSerializer: new MimeReaderWriter(),
                options: $this->tableOptions
            );

            foreach ($this->getTableMiddleware() as $middleware) {
                $proxy->pushMiddleware($middleware);
            }

            $this->table = new TableConnection($this, $proxy);
        }

        return $this->table;
    }



}
