<?php

namespace Shrd\Laravel\Azure\Storage\Blobs;


use DateTimeInterface;
use Shrd\Laravel\Azure\Storage\Authentication\AzureStorageConnectionString;
use Shrd\Laravel\Files\Upload\Contracts\UploadFileRequest;

readonly class BlobStorageUploadFileRequest implements UploadFileRequest
{

    public function __construct(protected string $blobEndpoint,
                                protected string $container,
                                protected string $blob,
                                protected string $sasToken,
                                protected string $contentType,
                                protected DateTimeInterface $startsAt,
                                protected DateTimeInterface $expiresAt)
    {
    }

    public function getBlobEndpoint(): string
    {
        return rtrim($this->blobEndpoint, '/');
    }

    public function getContainer(): string
    {
        return $this->container;
    }

    public function getBlob(): string
    {
        return trim($this->blob, '/');
    }

    public function getSasToken(): string
    {
        return $this->sasToken;
    }

    public function getObjectUri(): string
    {
        return $this->getBlobEndpoint().'/'.$this->getContainer().'/'.$this->getBlob();
    }

    public function getUri(): string
    {
        return $this->getObjectUri().'?'.$this->getSasToken();
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getConnectionString(): AzureStorageConnectionString
    {
        return AzureStorageConnectionString::fromIterable([
            "BlobEndpoint" => $this->getBlobEndpoint(),
            "SharedAccessSignature" => $this->getSasToken(),
        ]);
    }

    public function getStartsAt(): DateTimeInterface
    {
        return $this->startsAt;
    }

    public function getExpiresAt(): DateTimeInterface
    {
        return $this->expiresAt;
    }

}
