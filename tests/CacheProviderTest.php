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

        $cacheControl = $res->getHeader('Cache-Control');
        $cacheControl = reset($cacheControl);

        $this->assertEquals('private, max-age=43200', $cacheControl);
    }

    public function testDenyCache()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->denyCache(new Response());

        $cacheControl = $res->getHeader('Cache-Control');
        $cacheControl = reset($cacheControl);

        $this->assertEquals('no-store,no-cache', $cacheControl);
    }

    public function testWithExpires()
    {
        $now = time();
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withExpires(new Response(), $now);

        $expires = $res->getHeader('Expires');
        $expires = reset($expires);

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $expires);
    }

    public function testWithETag()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag(new Response(), $etag);

        $etagHeader = $res->getHeader('ETag');
        $etagHeader = reset($etagHeader);

        $this->assertEquals('"' . $etag . '"', $etagHeader);
    }

    public function testWithETagWeak()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag(new Response(), $etag, 'weak');

        $etagHeader = $res->getHeader('ETag');
        $etagHeader = reset($etagHeader);

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

        $lastModified = $res->getHeader('Last-Modified');
        $lastModified = reset($lastModified);

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $lastModified);
    }
}
