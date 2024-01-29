<?php

namespace Shrd\Laravel\Azure\Storage\Middleware;

use MicrosoftAzure\Storage\Common\Middlewares\RetryMiddleware;
use MicrosoftAzure\Storage\Common\Middlewares\RetryMiddlewareFactory as RetryMiddlewareFactoryAlias;

class RetryMiddlewareFactory extends RetryMiddlewareFactoryAlias
{
    public static function fromConfig(array $config): RetryMiddleware
    {
        return self::create(
            type:                $config['type'] ?? self::GENERAL_RETRY_TYPE,
            numberOfRetries:     $config['tries'] ?? 3,
            interval:            $config['interval'] ?? 1000,
            accumulationMethod: ($config['increase'] ?? 'linear') === 'exponential'
                ? RetryMiddlewareFactoryAlias::EXPONENTIAL_INTERVAL_ACCUMULATION
                : RetryMiddlewareFactoryAlias::LINEAR_INTERVAL_ACCUMULATION,
            retryConnect:        boolval($config['retry_connect'] ?? false),
        );
    }
}
