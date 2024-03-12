<?php

namespace Shrd\Laravel\Azure\Storage;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter as AzureBlobStorageAdapterAlias;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Internal\IBlob;
use MicrosoftAzure\Storage\Queue\Internal\IQueue;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Table\Internal\ITable;
use MicrosoftAzure\Storage\Table\TableRestProxy;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\Blobs\AzureBlobStorageAdapter;
use Shrd\Laravel\Azure\Storage\Blobs\BlobService;
use Shrd\Laravel\Azure\Storage\Queues\Arrays\ArrayQueueManager;
use Shrd\Laravel\Azure\Storage\Queues\AzureStorageQueueConnector;
use Shrd\Laravel\Azure\Storage\Queues\QueueService;
use Shrd\Laravel\Azure\Storage\Tables\TableConnection;

class ServiceProvider extends BaseServiceProvider
{

    public function register(): void
    {
        $this->app->singleton(AzureStorageService::class);

        $this->app->bind(StorageAccount::class, function (Container $app, array $config) {
            $connection = $config['connection'] ?? null;
            $service = $app[AzureStorageService::class];

            if($service->hasAccount($connection)) {
                return $app[AzureStorageService::class]->account($connection);
            } else {
                return $app[AzureStorageService::class]->createAccount($config);
            }
        });

        $this->registerBlobServices();
        $this->registerBlobAdapter();
        $this->registerTable();
        $this->registerQueue();
    }

    public function boot(): void
    {
        $this->bootBlob();
        $this->bootQueue();

        $this->publishes([
            __DIR__.'/../config/azure-storage.php' => config_path('azure-storage.php')
        ]);
    }

    protected function registerBlobServices(): void
    {
        $this->app->bind(BlobService::class, function (Container $app, array $config) {
            $account = $app->make(StorageAccount::class, $config);
            return $account->blob();
        });

        $this->app->bind(BlobRestProxy::class, function (Container $app, array $config) {
            $blobService = $app->make(BlobService::class, $config);
            return $blobService->proxy();
        });

        $this->app->bind(IBlob::class, BlobRestProxy::class);
    }

    protected function registerBlobAdapter(): void
    {
        $this->app->bind(AzureBlobStorageAdapter::class, function(Container $app, array $config) {
            $azureStorage = $app[AzureStorageService::class];
            $blobService = $azureStorage->blob($config['connection'] ?? null);

            return new AzureBlobStorageAdapter(
                blobService: $blobService,
                container: $config['container'],
                prefix: $config['prefix'] ?? '',
                mimeTypeDetector: $config['mimeTypeDetector'] ?? null,
                maxResultsForContentsListing: $config['maxResultsForContentsListing'] ?? 5000,
                visibilityHandling: $config['visibilityHandling'] ?? AzureBlobStorageAdapterAlias::ON_VISIBILITY_THROW_ERROR,
            );
        });

        $this->app->bind(
            AzureBlobStorageAdapterAlias::class,
            AzureBlobStorageAdapter::class
        );
    }

    protected function bootBlob(): void
    {
        Storage::extend('azure-storage', function (Container $app, array $config) {
            $adapter = $app->make(AzureBlobStorageAdapter::class, $config);
            return new FilesystemAdapter(
                driver: new Filesystem(
                    adapter: $adapter,
                ),
                adapter: $adapter,
                config: $config,
            );
        });
    }

    protected function registerTable(): void
    {
        $this->app->bind(TableConnection::class, function (Container $app, array $config) {
            $account = $app->make(StorageAccount::class, $config);
            return $account->table();
        });

        $this->app->bind(TableRestProxy::class, function (Container $app, array $config) {
            return $app->make(TableConnection::class, $config)->getProxy();
        });

        $this->app->bind(ITable::class, TableRestProxy::class);
    }

    protected function registerQueue(): void
    {
        $this->app->bind(QueueService::class, function (Container $app, array $config) {
            $account = $app->make(StorageAccount::class, $config);
            return $account->queue();
        });

        $this->app->bind(QueueRestProxy::class, function (Container $app, array $config) {
            return $this->app->make(QueueService::class, $config)->proxy();
        });

        $this->app->singleton(ArrayQueueManager::class);

        $this->app->bind(IQueue::class, QueueRestProxy::class);
    }

    protected function bootQueue(): void
    {
        Queue::extend('azure-storage', function(Container $app) {
            return $app->make(AzureStorageQueueConnector::class);
        });
    }
}
