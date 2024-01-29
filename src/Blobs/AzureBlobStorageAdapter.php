<?php

namespace Shrd\Laravel\Azure\Storage\Blobs;

use DateTimeInterface;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter as BaseAzureBlobStorageAdapter;
use League\Flysystem\Config;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use League\MimeTypeDetection\MimeTypeDetector;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use Ramsey\Uuid\Uuid;
use Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures\BlobStorageSignedPermission;
use Shrd\Laravel\Files\Upload\Contracts\UploadFileRequest;
use Shrd\Laravel\Files\Upload\Contracts\UploadFileRequestGenerator;
use Throwable;

/**
 * Blob storage adapter
 */
class AzureBlobStorageAdapter extends BaseAzureBlobStorageAdapter implements TemporaryUrlGenerator, UploadFileRequestGenerator
{
    protected PathPrefixer $prefixer;

    protected BlobRestProxy $client;

    /**
     * Create a new AzureBlobStorageAdapter instance.
     *
     * @param BlobService $blobService
     * @param string $container Container.
     * @param string $prefix Prefix.
     * @param MimeTypeDetector|null $mimeTypeDetector
     * @param int $maxResultsForContentsListing
     * @param string $visibilityHandling
     */
    public function __construct(
        protected BlobService $blobService,
        protected string $container = '$root',
        string $prefix = '',
        MimeTypeDetector $mimeTypeDetector = null,
        int $maxResultsForContentsListing = 5000,
        string $visibilityHandling = self::ON_VISIBILITY_THROW_ERROR,
    ) {
        $this->client = $this->blobService->proxy();
        parent::__construct(
            client: $this->client,
            container: $container,
            prefix: $prefix,
            mimeTypeDetector: $mimeTypeDetector,
            maxResultsForContentsListing: $maxResultsForContentsListing,
            visibilityHandling: $visibilityHandling,
            serviceSettings: $this->blobService->serviceSettings(),
        );

        $this->prefixer = new PathPrefixer($prefix);
    }

    public function getServiceSettings(): StorageServiceSettings
    {
        return $this->blobService->serviceSettings();
    }

    public function getEndpointUri(): string {
        return $this->blobService->getEndpoint();
    }

    public function getContainerUri(): string {
        $container = $this->container;
        if($container === '$root') {
            return $this->getEndpointUri();
        } else {
            return $this->blobService->getEndpoint($this->container);
        }
    }

    public function getHost(): string {
        return $this->blobService->getHost();
    }

    /**
     * Get the file URL by given path.
     *
     * @param string $path Path.
     *
     * @return string
     */
    public function getUrl(string $path): string
    {
        return $this->publicUrl($path, new Config());
    }

    public function publicUrl(string $path, Config $config): string
    {
        $container = $this->container;
        if($container === '$root') {
            return $this->blobService->getPublicEndpoint().'/'.ltrim($path, '/');
        }

        return $this->blobService->getPublicEndpoint($this->container, $path);
    }

    public function getTemporaryUrl(string $path, DateTimeInterface $expiresAt, Config|array $config = []): string
    {
        if(!$config instanceof Config) $config = new Config($config);
        return $this->temporaryUrl($path, $expiresAt, $config);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        try {
            return $this->blobService->clientRequest($this->container)
                ->blob($this->prefixer->prefixPath($path))
                ->allow(BlobStorageSignedPermission::READ)
                ->expiresAt($expiresAt)
                ->ip($config->get('signed_ip', ''))
                ->identifier($config->get('signed_identifier', ''))
                ->cacheControl($config->get('cache_control', ''))
                ->contentDisposition($config->get('content_disposition', ''))
                ->contentEncoding($config->get('content_encoding', ''))
                ->contentLanguage($config->get('content_language', ''))
                ->contentType($config->get('content_type', ''))
                ->getSignedUrl();
        } catch (Throwable $exception) {
            throw UnableToGenerateTemporaryUrl::dueToError($path, $exception);
        }
    }

    public function createUploadFileRequest(DateTimeInterface $startsAt,
                                            DateTimeInterface $expiresAt,
                                            ?string           $contentType = null): UploadFileRequest
    {
        $blob = $this->prefixer->prefixPath(Uuid::uuid4());

        $builder = $this->blobService
            ->clientRequest($this->container)
            ->blob($blob)
            ->allow(BlobStorageSignedPermission::CREATE, BlobStorageSignedPermission::WRITE)
            ->startsAt($startsAt)
            ->expiresAt($expiresAt);

        if($contentType) $builder = $builder->contentType($contentType);

        return new BlobStorageUploadFileRequest(
            blobEndpoint: $builder->getPublicEndpoint(),
            container: $builder->getContainer(),
            blob: $builder->getBlob(),
            sasToken: $builder->getSasToken(),
            contentType: $builder->getContentType() ?: null,
            startsAt: $builder->getStartsAt(),
            expiresAt: $builder->getExpiresAt()
        );
    }
}
