<?php

namespace Shrd\Laravel\Azure\Storage\Tests\Feature\Blobs;


use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageException;
use Shrd\Laravel\Azure\Storage\Tests\TestCase;

class BlobServiceTest extends TestCase
{
    public function test_ensureContainerExists_creates_containers()
    {
        $blob = $this->makeStorageAccount()->blob();

        $proxy = $blob->proxy();
        $container = $this->fake()->bothify('test-??????????');

        $this->assertNotContains($container, collect($proxy->listContainers()->getContainers())->map->getName());

        $blob->ensureContainerExists($container);

        try {
            $this->assertContains($container, collect($proxy->listContainers()->getContainers())->map->getName());

            $blob->ensureContainerExists($container);
        } finally {
            $proxy->deleteContainer($container);
        }
    }

    /**
     * @return void
     * @throws AzureStorageException
     */
    public function test_creates_containers_and_puts_files()
    {
        $service = $this->makeStorageAccount()->blob();

        $container = $this->fake()->bothify('test-??????????');

        try {
            $service->createContainer($container);
            $service->proxy->createAppendBlob($container, 'test-blob');
            $service->proxy->appendBlock($container, 'test-blob', 'AAA');

            $response = $service->proxy->getBlob($container, 'test-blob');
            $contents = stream_get_contents($response->getContentStream());

            $this->assertEquals('AAA', $contents);
        } finally {
            $service->proxy->deleteContainer($container);
        }
    }
}
