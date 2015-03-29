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

        $this->assertEquals('private, max-age=43200', $res->getHeader('Cache-Control'));
    }

    public function testDenyCache()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->denyCache(new Response());

        $this->assertEquals('no-store,no-cache', $res->getHeader('Cache-Control'));
    }

    public function testWithExpires()
    {
        $now = time();
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withExpires(new Response(), $now);

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $res->getHeader('Expires'));
    }

    public function testWithETag()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag(new Response(), $etag);

        $this->assertEquals('"' . $etag . '"', $res->getHeader('ETag'));
    }

    public function testWithETagWeak()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag(new Response(), $etag, 'weak');

        $this->assertEquals('W/"' . $etag . '"', $res->getHeader('ETag'));
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

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $res->getHeader('Last-Modified'));
    }
}
