<?php
namespace Slim\HttpCache;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Cache
{
    /**
     * Cache-Control type (public or private)
     *
     * @var string
     */
    protected $type;

    /**
     * Cache-Control max age in seconds
     *
     * @var int
     */
    protected $maxAge;

    /**
     * Create new HTTP cache
     *
     * @param string $type   The cache type: "public" or "private"
     * @param int    $maxAge The maximum age of client-side cache
     */
    public function __construct($type = 'private', $maxAge = 86400)
    {
        $this->type = $type;
        $this->maxAge = $maxAge;
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
        $response = $next($request, $response);

        // Cache-Control header
        $response = $response->withHeader('Cache-Control', sprintf(
            '%s, max-age=%s',
            $this->type,
            $this->maxAge
        ));

        // Last-Modified header and conditional GET check
        $lastModified = $response->getHeader('Last-Modified');
        if ($lastModified) {
            if (!is_integer($lastModified)) {
                $lastModified = strtotime($lastModified);
            }
            $ifModifiedSince = $request->getHeader('If-Modified-Since');
            if ($ifModifiedSince && $lastModified === strtotime($ifModifiedSince)) {
                return $response->withStatus(304);
            }
        }

        // ETag header and conditional GET check
        $etag = $response->getHeader('ETag');
        if ($etag) {
            $ifNoneMatch = $request->getHeader('If-None-Match');
            if ($ifNoneMatch) {
                $etagList = preg_split('@\s*,\s*@', $ifNoneMatch);
                if (in_array($etag, $etagList) || in_array('*', $etagList)) {
                    return $response->withStatus(304);
                }
            }
        }

        return $response;
    }
}
