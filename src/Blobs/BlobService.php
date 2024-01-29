<?php

namespace Shrd\Laravel\Azure\Storage\Blobs;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures\SharedKeyUrlBuilder;
use Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures\UrlBuilder;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageException;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;

readonly class BlobService
{
    public BlobRestProxy $proxy;

    public function __construct(protected StorageAccount $storageAccount,
                                protected array $middleware = [],
                                protected array $options = [])
    {
        $settings = $storageAccount->serviceSettings();
        $proxy = new BlobRestProxy(
            primaryUri: $settings->getBlobEndpointUri(),
            secondaryUri: $settings->getBlobSecondaryEndpointUri(),
            accountName: $settings->getName(),
            options: $this->options,
        );

        foreach ($this->middleware as $middleware) {
            $proxy->pushMiddleware($middleware);
        }

        $this->proxy = $proxy;
    }

    public function getStorageAccount(): StorageAccount
    {
        return $this->storageAccount;
    }

    public function serviceSettings(): StorageServiceSettings
    {
        return $this->storageAccount->serviceSettings();
    }

    public function sasHelper(): BlobSharedAccessSignatureHelper
    {
        $settings = $this->serviceSettings();
        return new BlobSharedAccessSignatureHelper(
            accountName: $settings->getName(),
            accountKey: $settings->getKey(),
        );
    }

    public function getEndpoint(?string $container = null, ?string $blob = null): string
    {
        $result = rtrim($this->storageAccount->serviceSettings()->getBlobEndpointUri(), '/');
        if(!$container) return $result;
        $result .= '/'.trim($container, '/');

        if(!$blob) return $result;
        $result .= '/'.trim($blob, '/');

        return $result;
    }

    public function getHost(): string
    {
        return parse_url($this->getEndpoint(), PHP_URL_HOST);
    }

    public function getPublicEndpoint(?string $container = null, ?string $blob = null): string
    {
        $result = rtrim($this->storageAccount->getBlobPublicEndpoint(), '/');

        if(!$container) return $result;
        $result .= '/'.trim($container, '/');

        if(!$blob) return $result;
        $result .= '/'.trim($blob, '/');

        return $result;
    }

    public function clientRequest(string $container): UrlBuilder
    {
        return new SharedKeyUrlBuilder(
            sasHelper: $this->sasHelper(),
            publicEndpoint: $this->getPublicEndpoint(),
            container: $container
        );
    }

    public function ensureContainerExists(string $container): static
    {
        try {
            $this->proxy->createContainer($container);
        } catch (ServiceException $exception) {
            if($exception->getCode() !== 409)  throw $exception;
        }
        return $this;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function createContainer(string                 $container,
                                    ?CreateContainerOptions $options = null): static
    {
        try {
            $this->proxy->createContainer($container, $options);
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
        return $this;
    }

    public function proxy(): BlobRestProxy
    {
        return $this->proxy;
    }
}
