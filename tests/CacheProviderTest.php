<?php
namespace Slim\HttpCache\Tests;

use Psr\Http\Message\ResponseInterface;
use Slim\HttpCache\CacheProvider;
use Slim\Psr7\Factory\ResponseFactory;

class CacheProviderTest extends \PHPUnit_Framework_TestCase
{
    private function createResponse(): ResponseInterface
    {
        $responseFactory = new ResponseFactory();
        return $responseFactory->createResponse();
    }

    public function testAllowCache()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->allowCache($this->createResponse(), 'private', 43200);

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('private, max-age=43200', $cacheControl);
    }

    public function testAllowCacheWithMustRevalidate()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->allowCache($this->createResponse(), 'private', 43200, true);

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('private, max-age=43200, must-revalidate', $cacheControl);
    }

    public function testDenyCache()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->denyCache($this->createResponse());

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('no-store,no-cache', $cacheControl);
    }

    public function testWithExpires()
    {
        $now = time();
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withExpires($this->createResponse(), $now);

        $expires = $res->getHeaderLine('Expires');

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $expires);
    }

    public function testWithETag()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag($this->createResponse(), $etag);

        $etagHeader = $res->getHeaderLine('ETag');

        $this->assertEquals('"' . $etag . '"', $etagHeader);
    }

    public function testWithETagWeak()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag($this->createResponse(), $etag, 'weak');

        $etagHeader = $res->getHeaderLine('ETag');

        $this->assertEquals('W/"' . $etag . '"', $etagHeader);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithETagInvalidType()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $cacheProvider->withEtag($this->createResponse(), $etag, 'bork');
    }

    public function testWithLastModified()
    {
        $now = time();
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withLastModified($this->createResponse(), $now);

        $lastModified = $res->getHeaderLine('Last-Modified');

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $lastModified);
    }
}
