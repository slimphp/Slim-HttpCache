<?php
/**
 * Slim Framework (https://www.slimframework.com)
 *
 * @license   https://github.com/slimphp/Slim-HttpCache/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\HttpCache\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\HttpCache\Cache;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

use function gmdate;
use function time;

class CacheTest extends TestCase
{
    public function requestFactory(): ServerRequestInterface
    {
        $serverRequestFactory = new ServerRequestFactory();

        return $serverRequestFactory->createServerRequest('GET', 'https://example.com:443/foo/bar?abc=123');
    }

    protected function createResponse(): ResponseInterface
    {
        $responseFactory = new ResponseFactory();

        return $responseFactory->createResponse();
    }

    /**
     * Create a request handler that simply assigns the $request that it receives to a public property
     * of the returned response, so that we can then inspect that request.
     *
     * @param ResponseInterface|null $response
     *
     * @return RequestHandlerInterface
     */
    protected function createRequestHandler(ResponseInterface $response = null): RequestHandlerInterface
    {
        $response = $response ?? $this->createResponse();

        return new class($response) implements RequestHandlerInterface {
            private $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    public function testCacheControlHeader()
    {
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory();

        $res = $cache->process($req, $this->createRequestHandler());

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('public, max-age=86400', $cacheControl);
    }

    public function testCacheControlHeaderWithMustRevalidate()
    {
        $cache = new Cache('private', 86400, true);
        $req = $this->requestFactory();

        $res = $cache->process($req, $this->createRequestHandler());

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('private, max-age=86400, must-revalidate', $cacheControl);
    }

    public function testCacheControlHeaderWithZeroMaxAge()
    {
        $cache = new Cache('private', 0, false);
        $req = $this->requestFactory();

        $res = $cache->process($req, $this->createRequestHandler());

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('private, no-cache', $cacheControl);
    }

    public function testCacheControlHeaderDoesNotOverrideExistingHeader()
    {
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory();

        $res = $this->createResponse()->withHeader('Cache-Control', 'no-cache,no-store');
        $res = $cache->process($req, $this->createRequestHandler($res));

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('no-cache,no-store', $cacheControl);
    }

    public function testLastModifiedWithCacheHit()
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now + 86400);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now + 86400);
        $cache = new Cache('public', 86400);

        $req = $this->requestFactory()->withHeader('If-Modified-Since', $ifModifiedSince);

        $res = $this->createResponse()->withHeader('Last-Modified', $lastModified);
        $res = $cache->process($req, $this->createRequestHandler($res));

        $this->assertEquals(304, $res->getStatusCode());
    }

    public function testLastModifiedWithCacheHitAndNewerDate()
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now + 86400);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now + 172800); // <-- Newer date
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-Modified-Since', $ifModifiedSince);

        $res = $this->createResponse()->withHeader('Last-Modified', $lastModified);
        $res = $cache->process($req, $this->createRequestHandler($res));

        $this->assertEquals(304, $res->getStatusCode());
    }

    public function testLastModifiedWithCacheHitAndOlderDate()
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now + 86400);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now); // <-- Older date
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-Modified-Since', $ifModifiedSince);

        $res = $this->createResponse()->withHeader('Last-Modified', $lastModified);
        $res = $cache->process($req, $this->createRequestHandler($res));

        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testLastModifiedWithCacheMiss()
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now + 86400);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now - 86400);
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-Modified-Since', $ifModifiedSince);

        $res = $this->createResponse()->withHeader('Last-Modified', $lastModified);
        $res = $cache->process($req, $this->createRequestHandler($res));

        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testETagWithCacheHit()
    {
        $etag = 'abc';
        $ifNoneMatch = 'abc';
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-None-Match', $ifNoneMatch);

        $res = $this->createResponse()->withHeader('Etag', $etag);
        $res = $cache->process($req, $this->createRequestHandler($res));

        $this->assertEquals(304, $res->getStatusCode());
    }

    public function testETagWithCacheMiss()
    {
        $etag = 'abc';
        $ifNoneMatch = 'xyz';
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-None-Match', $ifNoneMatch);

        $res = $this->createResponse()->withHeader('Etag', $etag);
        $res = $cache->process($req, $this->createRequestHandler($res));

        $this->assertEquals(200, $res->getStatusCode());
    }
}
