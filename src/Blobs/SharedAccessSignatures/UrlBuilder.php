<?php

namespace Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures;

use Carbon\Carbon;
use DateTime;
use DateTimeInterface;
use Faker\Provider\Uuid;
use Illuminate\Support\Str;
use MicrosoftAzure\Storage\Common\Internal\Resources;

abstract class UrlBuilder
{
    protected ?string $blob = null;

    protected DateTime $signedExpiry;
    protected DateTime $signedStart;

    protected string $publicEndpoint;
    protected string $container;

    /**
     * @var array<BlobStorageSignedPermission> $signedPermissions
     */
    protected array $signedPermissions = [];

    protected string $signedIP = '';
    protected bool $allowHttp = true;
    protected string $signedIdentifier = '';
    protected string $cacheControl = '';

    protected string $contentDisposition = '';
    protected string $contentEncoding = '';
    protected string $contentLanguage = '';
    protected string $contentType = '';

    public function __construct(string $publicEndpoint, string $container)
    {
        $this
            ->publicEndpoint($publicEndpoint)
            ->container($container);

        $this->allowHttp = !Str::startsWith('https', $publicEndpoint);
        $this->signedExpiry = Carbon::now();
        $this->signedStart = Carbon::now();
    }

    public function publicEndpoint(string $endpoint): static
    {
        $this->publicEndpoint = rtrim($endpoint, '/');
        return $this;
    }

    public function container(string $container): static
    {
        $this->container = $container;
        return $this;
    }

    public function getSignedResource(): string
    {
        if($this->blob) {
            return Resources::RESOURCE_TYPE_BLOB;
        } else {
            return Resources::RESOURCE_TYPE_CONTAINER;
        }
    }

    public function getResourceName(): string
    {
        $result = $this->container;
        if($this->blob) {
            $result .= '/'.$this->blob;
        }
        return $result;
    }

    public function getBlob(): ?string
    {
        return $this->blob;
    }

    public function getContainer(): string
    {
        return $this->container;
    }

    public function blob(?string $name = null): static
    {
        $this->blob = $name ?? Uuid::uuid();
        return $this;
    }

    public function noBlob(): static
    {
        $this->blob = null;
        return $this;
    }

    public function allow(... $permissions): static
    {
        foreach ($permissions as $permission) {
            $this->signedPermissions[] = BlobStorageSignedPermission::coerce($permission);
        }
        return $this;
    }

    public function allowOnly(...$permissions): static
    {
        $parsed = [];
        foreach ($permissions as $permission) {
            $parsed[] = BlobStorageSignedPermission::coerce($permission);
        }
        $this->signedPermissions = $parsed;
        return $this;
    }

    public function deny(... $permissions): static
    {
        $remove = [];
        foreach ($permissions as $permission) {
            $remove = BlobStorageSignedPermission::coerce($permission);
        }
        $this->signedPermissions[] = array_diff($this->signedPermissions, $remove);
        return $this;
    }

    public function getSignedPermissionsString(): string
    {
        return BlobStorageSignedPermission::toSignedPermissionString($this->signedPermissions);
    }

    public function getPermissions(): array
    {
        return BlobStorageSignedPermission::normalize($this->signedPermissions);
    }

    public function getPublicEndpoint(): string
    {
        return $this->publicEndpoint;
    }

    public function ip(string $ip): static
    {
        $this->signedIP = $ip;
        return $this;
    }

    public function getIp(): string
    {
        return $this->signedIP;
    }

    public function allowHttp(bool $value = true): static
    {
        $this->allowHttp = $value;
        return $this;
    }

    public function noHttp(): static
    {
        return $this->allowHttp(false);
    }

    public function getSignedProtocol(): string
    {
        if($this->allowHttp) {
            return 'https,http';
        } else {
            return 'https';
        }
    }

    public function cacheControl(string $cacheControl): static
    {
        $this->cacheControl = $cacheControl;
        return $this;
    }

    public function getCacheControl(): string
    {
        return $this->cacheControl;
    }

    public function contentEncoding(string $contentEncoding): static
    {
        $this->contentEncoding = $contentEncoding;
        return $this;
    }

    public function getContentEncoding(): string
    {
        return $this->contentEncoding;
    }

    public function contentLanguage(string $contentLanguage): static
    {
        $this->contentLanguage = $contentLanguage;
        return $this;
    }

    public function getContentLanguage(): string
    {
        return $this->contentLanguage;
    }

    public function contentType(string $contentType): static
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function expiresAt(DateTimeInterface $dateTime): static
    {
        $this->signedExpiry = Carbon::createFromInterface($dateTime);
        return $this;
    }

    public function expiresAdd($value, $unit = 1, $overflow = null): static
    {
        $this->signedExpiry = $this->signedExpiry->add($value, $unit, $overflow);
        return $this;
    }

    public function startsAt(DateTimeInterface $dateTime): static
    {
        $this->signedStart = Carbon::createFromInterface($dateTime);
        return $this;
    }

    public function startsAdd($value, $unit = 1, $overflow = null): static
    {
        $this->signedStart = $this->signedStart->add($value, $unit, $overflow);
        return $this;
    }

    public function getStartsAt(): Carbon
    {
        return Carbon::make($this->signedStart);
    }

    public function getExpiresAt(): Carbon
    {
        return Carbon::make($this->signedExpiry);
    }

    public function identifier(string $identifier): static
    {
        $this->signedIdentifier = $identifier;
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->signedIdentifier;
    }

    public function contentDisposition(string $contentDisposition): static
    {
        $this->contentDisposition = $contentDisposition;
        return $this;
    }

    public function getContentDisposition(): string
    {
        return $this->contentDisposition;
    }

    public function getUrl(): string
    {
        $result = $this->publicEndpoint. '/'.$this->container;
        if($this->blob) {
            $result .= '/'.$this->blob;
        }
        return $result;
    }

    abstract public function getSasToken(): string;

    public function get(): BlobClientRequest
    {
        return new BlobClientRequest(
            publicEndpoint: $this->publicEndpoint,
            sasToken: $this->getSasToken(),
            container: $this->container,
            startsAt: $this->getStartsAt(),
            expiresAt: $this->getExpiresAt(),
            blob: $this->blob,
            contentType: $this->contentType ?: null,
        );
    }

    public function getSignedUrl(): string
    {
        $result = $this->getUrl();
        $result .= '?'. $this->getSasToken();
        return $result;
    }
}
