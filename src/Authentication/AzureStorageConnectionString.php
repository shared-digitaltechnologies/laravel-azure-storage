<?php

namespace Shrd\Laravel\Azure\Storage\Authentication;

use Illuminate\Support\Str;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use Shrd\Laravel\Azure\ConnectionStrings\ConnectionString;

final class AzureStorageConnectionString extends ConnectionString
{
    const DEVELOPMENT_STORAGE="UseDevelopmentStorage=true";
    const AZURITE_ACCOUNT_NAME = Resources::DEV_STORE_NAME;
    const AZURITE_ACCOUNT_KEY = Resources::DEV_STORE_KEY;

    public static function developmentStorage(): self
    {
        return self::fromString(self::DEVELOPMENT_STORAGE);
    }

    /** @noinspection HttpUrlsUsage */
    public static function azurite(string $host = "127.0.0.1",
                                   ?string $accountName = null,
                                   ?string $accountKey = null): self
    {
        $accountName ??= self::AZURITE_ACCOUNT_NAME;
        $accountKey ??= self::AZURITE_ACCOUNT_KEY;
        return self::fromIterable([
            "DefaultEndpointsProtocol" => "http",
            "AccountName" => $accountName,
            "AccountKey" => $accountKey,
            "BlobEndpoint"  => "http://$host:10000/". $accountName,
            "QueueEndpoint" => "http://$host:10001/". $accountName,
            "TableEndpoint" => "http://$host:10002/". $accountName,
        ]);
    }

    public static function fromConfig(array $config = []): self
    {
        $connectionString = $config['connection_string'] ?? null;
        if(is_string($connectionString) && trim($connectionString) !== '') {
            return self::fromString($connectionString);
        }

        $properties = [];
        if(!empty($config['account_name'])) {
            $properties['AccountName'] = $config['account_name'];
        }

        if(!empty($config['account_key'])) {
            $properties['AccountKey'] = $config['account_key'];
        }

        if(!empty($config['use_development_storage'])) {
            $properties['UseDevelopmentStorage'] = 'true';
        }

        if(!empty($config['proxy_uri'])) {
            $properties['DevelopmentStorageProxyUri'] = $config['proxy_uri'];
        }

        if(!empty($config['sas'])) {
            $properties['SharedAccessSignature'] = $config['sas'];
        }

        if(!empty($config['protocol'])) {
            $properties['DefaultEndpointsProtocol'] = Str::lower($config['protocol']);
        }

        if(!empty($config['blob']['endpoint'])) {
            $properties['BlobEndpoint'] = Str::lower($config['blob']['endpoint']);
        }

        if(!empty($config['table']['endpoint'])) {
            $properties['TableEndpoint'] = Str::lower($config['table']['endpoint']);
        }

        if(!empty($config['queue']['endpoint'])) {
            $properties['QueueEndpoint'] = Str::lower($config['queue']['endpoint']);
        }

        if(!empty($config['file']['endpoint'])) {
            $properties['FileEndpoint'] = Str::lower($config['file']['endpoint']);
        }

        return self::fromIterable($properties);
    }

    public function getServiceSettings(): StorageServiceSettings
    {
        if(!$this->hasAccountKey() && !$this->hasSharedAccessSignature()) {
            return StorageServiceSettings::createFromConnectionStringForTokenCredential($this->toString());
        }

        return StorageServiceSettings::createFromConnectionString($this->toString());
    }

    public function getDevelopmentStorageProxyUri(): string
    {
        return $this->get('DevelopmentStorageProxyUri', 'http://127.0.0.1');
    }

    public function isDevelopmentStorage(): bool
    {
        return $this->get('UseDevelopmentStorage') === 'true';
    }

    public function getAccountName(): ?string
    {
        return $this->get('AccountName', $this->isDevelopmentStorage() ? self::AZURITE_ACCOUNT_NAME : null);
    }

    public function getAccountKey(): ?string
    {
        return $this->get('AccountKey', $this->isDevelopmentStorage() ? self::AZURITE_ACCOUNT_KEY : null);
    }

    public function hasAccountKey(): bool
    {
        return $this->has('AccountKey') || $this->isDevelopmentStorage();
    }


    public function getSharedAccessSignature(): ?string
    {
        return $this->get('SharedAccessSignature');
    }

    public function hasSharedAccessSignature(): bool
    {
        return $this->has('SharedAccessSignature');
    }

    public function getDefaultEndpointsProtocol(): string
    {
        return $this->get(
            'DefaultEndpointsProtocol',
            $this->isDevelopmentStorage() ? 'http' : 'https'
        );
    }

    public function getBlobEndpoint(): ?string
    {
        return $this->get(
            'BlobEndpoint',
            $this->isDevelopmentStorage()
                ? $this->getDevelopmentStorageProxyUri() . ':10000/' . $this->getAccountName()
                : null
        );
    }

    public function getQueueEndpoint(): ?string
    {
        return $this->get(
            'QueueEndpoint',
            $this->isDevelopmentStorage()
                ? $this->getDevelopmentStorageProxyUri().':10001/'.$this->getAccountName()
                : null
        );
    }

    public function getTableEndpoint(): ?string
    {
        return $this->get(
            'TableEndpoint',
            $this->isDevelopmentStorage()
                ? $this->getDevelopmentStorageProxyUri().':10002/' . $this->getAccountName()
                : null
        );
    }

    public function getFileEndpoint(): ?string
    {
        return $this->get('FileEndpoint');
    }
}
