<?php

namespace Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures;

use DateTime;

readonly class BlobClientRequest
{
    public function __construct(public string $publicEndpoint,
                                public string $sasToken,
                                public string $container,
                                public DateTime $startsAt,
                                public DateTime $expiresAt,
                                public ?string $blob = null,
                                public ?string $contentType = null)
    {
    }

    public function getUrl(): string
    {
        $result = $this->publicEndpoint. '/'.$this->container;
        if($this->blob) {
            $result .= '/'.$this->blob;
        }
        return $result;
    }

    public function getSignedUrl(): string
    {
        return $this->getUrl().'?'.$this->sasToken;
    }
}
