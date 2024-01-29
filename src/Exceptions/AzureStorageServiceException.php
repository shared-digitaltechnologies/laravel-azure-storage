<?php

namespace Shrd\Laravel\Azure\Storage\Exceptions;

use Exception;
use JsonException;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use stdClass;
use Throwable;

/**
 * @property ?SimpleXMLElement $Code
 */
class AzureStorageServiceException extends Exception implements AzureStorageException
{
    public function __construct(public readonly ServiceException $serviceException,
                                ?string $message = null,
                                int $code = 1,
                                ?Throwable $previous = null)
    {
        parent::__construct(
            message: $message ?? $this->serviceException->getMessage(),
            code: $code ?? $this->serviceException->getCode(),
            previous: $previous ?? $this->serviceException
        );
    }

    public function getResponse(): ResponseInterface
    {
        return $this->serviceException->getResponse();
    }

    public function parse(): SimpleXMLElement|stdClass|null
    {
        $response = $this->getResponse();
        $body = $response->getBody();
        $body->rewind();
        $contents = $body->getContents();
        try {
            return new SimpleXMLElement($contents);
        } catch (Exception) {
            try {
                $response = \Safe\json_decode($contents);
                return $response->{"odata.error"} ?? null;
            } catch (JsonException) {
                return null;
            }
        }
    }

    /**
     * Gives the error code of the exception.
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        $parsed = $this->parse();

        $code = null;

        if($parsed instanceof SimpleXMLElement) $code = $parsed->Code ?? null;
        else if($parsed instanceof stdClass)    $code = $parsed->code ?? null;

        return $code ? strval($code) : null;
    }

    public function hasErrorCode(string $errorCode): bool
    {
        return $this->getErrorCode() === $errorCode;
    }

    public function __get(string $name): mixed
    {
        $parsed = $this->parse();
        return $parsed?->{$name};
    }
}
