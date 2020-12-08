<?php
/**
 * Slim Framework (https://www.slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-HttpCache/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\HttpCache;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function in_array;
use function is_array;
use function is_numeric;
use function preg_split;
use function reset;
use function sprintf;
use function strtotime;

class Cache implements MiddlewareInterface
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
     * Cache-Control includes must-revalidate flag
     *
     * @var bool
     */
    protected $mustRevalidate;

    /**
     * Create new HTTP cache
     *
     * @param string $type           The cache type: "public" or "private"
     * @param int    $maxAge         The maximum age of client-side cache
     * @param bool   $mustRevalidate must-revalidate
     */
    public function __construct(string $type = 'private', int $maxAge = 86400, bool $mustRevalidate = false)
    {
        $this->type = $type;
        $this->maxAge = $maxAge;
        $this->mustRevalidate = $mustRevalidate;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Cache-Control header
        if (!$response->hasHeader('Cache-Control')) {
            if ($this->maxAge === 0) {
                $response = $response->withHeader(
                    'Cache-Control',
                    sprintf(
                        '%s, no-cache%s',
                        $this->type,
                        $this->mustRevalidate ? ', must-revalidate' : ''
                    )
                );
            } else {
                $response = $response->withHeader(
                    'Cache-Control',
                    sprintf(
                        '%s, max-age=%s%s',
                        $this->type,
                        $this->maxAge,
                        $this->mustRevalidate ? ', must-revalidate' : ''
                    )
                );
            }
        }


        // ETag header and conditional GET check
        $etag = $response->getHeader('ETag');
        $etag = reset($etag);

        if ($etag) {
            $ifNoneMatch = $request->getHeaderLine('If-None-Match');

            if ($ifNoneMatch) {
                $etagList = preg_split('@\s*,\s*@', $ifNoneMatch);
                if (is_array($etagList) && (in_array($etag, $etagList) || in_array('*', $etagList))) {
                    return $response->withStatus(304);
                }
            }
        }


        // Last-Modified header and conditional GET check
        $lastModified = $response->getHeaderLine('Last-Modified');

        if ($lastModified) {
            if (!is_numeric($lastModified)) {
                $lastModified = strtotime($lastModified);
            }

            $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');

            if ($ifModifiedSince && $lastModified <= strtotime($ifModifiedSince)) {
                return $response->withStatus(304);
            }
        }

        return $response;
    }
}
