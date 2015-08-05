<?php
namespace Slim\Middleware\HttpCache;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Cache
{
    /**
     * @var CacheHelper
     */
    protected $cache;

    /**
     * Default Cache-Control type (public or private)
     *
     * @var string
     */
    protected $type;

    /**
     * Default Cache-Control max age in seconds
     *
     * @var int
     */
    protected $maxAge;

    /**
     * Create new HTTP cache
     *
     * @param string      $type   The cache type: "public" or "private"
     * @param int         $maxAge The maximum age of client-side cache
     * @param CacheHelper $cache  The cache object to use
     */
    public function __construct($type = 'private', $maxAge = 86400, CacheHelper $cache = null)
    {
        $this->type = $type;
        $this->maxAge = $maxAge;
        $this->cache = $cache ?: new CacheHelper();
    }

    /**
     * Invoke cache middleware
     *
     * @param  RequestInterface  $request  A PSR7 request object
     * @param  ResponseInterface $response A PSR7 response object
     * @param  callable          $next     The next middleware callable
     *
     * @return ResponseInterface           A PSR7 response object
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        /** @var ResponseInterface $response */
        $response = $next($request, $response);

        // Don't add cache headers if the request method is not safe
        if (!in_array($request->getMethod(), ['GET', 'HEAD'])) {
            return $response;
        }

        // Automatically add the default Cache-Control header
        if (!$response->hasHeader('Cache-Control')) {
            $response = $this->cache->allowCache($response, $this->type, $this->maxAge);
        }

        // Check if the client cache is still valid
        if ($response->getStatusCode() !== 304 && $this->cache->isStillValid($request, $response)) {
            return $response->withStatus(304);
        }

        return $response;
    }
}
