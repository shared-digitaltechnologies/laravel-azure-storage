<?php

namespace Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures;

use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;

class SharedKeyUrlBuilder extends UrlBuilder
{

    public function __construct(protected BlobSharedAccessSignatureHelper $sasHelper,
                                string $publicEndpoint,
                                string $container)
    {
        parent::__construct($publicEndpoint, $container);
    }

    public function getSasToken(): string
    {
        return $this->sasHelper->generateBlobServiceSharedAccessSignatureToken(
            signedResource: $this->getSignedResource(),
            resourceName: $this->getResourceName(),
            signedPermissions: $this->getSignedPermissionsString(),
            signedExpiry: $this->signedExpiry,
            signedStart: $this->signedStart,
            signedIP: $this->getIp(),
            signedProtocol: $this->getSignedProtocol(),
            signedIdentifier: $this->getIdentifier(),
            cacheControl: $this->getCacheControl(),
            contentDisposition: $this->getContentDisposition(),
            contentEncoding: $this->getContentEncoding(),
            contentLanguage: $this->getContentLanguage(),
            contentType: $this->getContentType(),
        );
    }
}
