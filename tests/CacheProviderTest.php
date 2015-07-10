<?php
namespace Slim\HttpCache\Tests;

use Slim\HttpCache\CacheProvider;
use Slim\Http\Response;

class CacheProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testAllowCache()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->allowCache(new Response(), 'private', 43200);

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('private, max-age=43200', $cacheControl);
    }

    public function testAllowCacheWithMustRevalidate()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->allowCache(new Response(), 'private', 43200, true);

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('private, max-age=43200, must-revalidate', $cacheControl);
    }

    public function testDenyCache()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->denyCache(new Response());

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('no-store,no-cache', $cacheControl);
    }

    public function testWithExpires()
    {
        $now = time();
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withExpires(new Response(), $now);

        $expires = $res->getHeaderLine('Expires');

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $expires);
    }

    public function testWithETag()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag(new Response(), $etag);

        $etagHeader = $res->getHeaderLine('ETag');

        $this->assertEquals('"' . $etag . '"', $etagHeader);
    }

    public function testWithETagWeak()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag(new Response(), $etag, 'weak');

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
        $cacheProvider->withEtag(new Response(), $etag, 'bork');
    }

    public function testWithLastModified()
    {
        $now = time();
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withLastModified(new Response(), $now);

        $lastModified = $res->getHeaderLine('Last-Modified');

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $lastModified);
    }
}
