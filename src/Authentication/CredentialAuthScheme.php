<?php

namespace Shrd\Laravel\Azure\Storage\Authentication;

use GuzzleHttp\Psr7\Request;
use MicrosoftAzure\Storage\Common\Internal\Authentication\IAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use Psr\Http\Message\MessageInterface;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

class CredentialAuthScheme implements IAuthScheme
{
    public function __construct(protected TokenCredential $credential)
    {
    }

    /**
     * @throws AzureCredentialException
     */
    protected function token(): AccessToken
    {
        return $this->credential->token(AzureScope::storageAccount());
    }

    /**
     * @throws AzureCredentialException
     */
    public function signRequest(Request $request): MessageInterface|Request
    {
        return $request->withHeader(
            Resources::AUTHENTICATION,
            $this->token()->getAuthorizationHeader()
        );
    }
}
