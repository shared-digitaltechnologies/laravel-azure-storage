<?php

namespace Shrd\Laravel\Azure\Storage\Tests\Unit\Blobs\SharedAccessSignatures;

use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use PHPUnit\Framework\TestCase;
use Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures\SharedKeyUrlBuilder;

class SharedKeyUrlBuilderTest extends TestCase
{
    public function test_builds_container_queries()
    {
        $publicEndpointBase = Resources::DEV_STORE_URI.':1000/'.Resources::DEV_STORE_NAME;

        $instance = new SharedKeyUrlBuilder(
            new BlobSharedAccessSignatureHelper(Resources::DEV_STORE_NAME, Resources::DEV_STORE_KEY),
            $publicEndpointBase,
            'test'
        );

        $this->assertEquals($publicEndpointBase.'/test', $instance->getUrl());
    }
}
