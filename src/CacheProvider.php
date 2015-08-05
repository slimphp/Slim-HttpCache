<?php
namespace Slim\Middleware\HttpCache;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class CacheProvider implements ServiceProviderInterface
{
    /**
     * Register this cache provider with a Pimple container
     *
     * @param  Container $container
     */
    public function register(Container $container)
    {
        $container['cache'] = function () {
            return new CacheHelper();
        };
    }
}
