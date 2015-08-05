<?php
namespace Slim\Middleware\HttpCache\Tests;

use Slim\Http\Response;
use Slim\Middleware\HttpCache\CacheHelper;

class CacheHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheHelper
     */
    protected $cache;

    protected function setUp()
    {
        $this->cache = new CacheHelper();
    }

    public function testAllowCache()
    {
        $res = $this->cache->allowCache(new Response(), 'private', 43200);

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('private, max-age=43200', $cacheControl);
    }

    public function testDenyCache()
    {
        $res = $this->cache->denyCache(new Response());

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('no-store,no-cache', $cacheControl);
    }

    public function testWithExpires()
    {
        $now = time();
        $res = $this->cache->withExpires(new Response(), $now);

        $expires = $res->getHeaderLine('Expires');

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $expires);
    }

    public function testWithETag()
    {
        $etag = 'abc';
        $res = $this->cache->withEtag(new Response(), $etag);

        $etagHeader = $res->getHeaderLine('ETag');

        $this->assertEquals('"' . $etag . '"', $etagHeader);
    }

    public function testWithETagWeak()
    {
        $etag = 'abc';
        $res = $this->cache->withEtag(new Response(), $etag, 'weak');

        $etagHeader = $res->getHeaderLine('ETag');

        $this->assertEquals('W/"' . $etag . '"', $etagHeader);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithETagInvalidType()
    {
        $etag = 'abc';
        $this->cache->withEtag(new Response(), $etag, 'bork');
    }

    public function testWithLastModified()
    {
        $now = time();
        $res = $this->cache->withLastModified(new Response(), $now);

        $lastModified = $res->getHeaderLine('Last-Modified');

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $now), $lastModified);
    }

    /**
     * @return array
     */
    public function modifiedTimes()
    {
        return [
            'same-time' => [0, true],
            'current-time-older' => [172800, true],
            'current-time-newer' => [-86400, false],
        ];
    }

    /**
     * @covers Slim\Middleware\HttpCache\CacheHelper::isStillValid
     * @dataProvider modifiedTimes
     * @param int $offsetLastRequest
     * @param bool $valid
     */
    public function testisValidWithLastModified($offsetLastRequest, $valid)
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now + $offsetLastRequest);

        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())
            ->method('getHeaderLine')
            ->with('If-Modified-Since')
            ->will($this->returnValue($ifModifiedSince));

        $res = new Response();
        $res = $res->withHeader('Last-Modified', $lastModified);

        $this->assertSame($valid, $this->cache->isStillValid($req, $res));
    }

    /**
     * @return array
     */
    public function eTags()
    {
        return [
            'hit' => ['abc', 'abc', true],
            'miss' => ['abc', 'xyz', false],
        ];
    }

    /**
     * @covers Slim\Middleware\HttpCache\CacheHelper::isStillValid
     * @dataProvider eTags
     * @param string $eTag
     * @param string $ifNoneMatch
     * @param bool $valid
     */
    public function testIsValidWithETag($eTag, $ifNoneMatch, $valid)
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())
            ->method('getHeaderLine')
            ->with('If-None-Match')
            ->will($this->returnValue($ifNoneMatch));

        $res = new Response();
        $res = $res->withHeader('ETag', $eTag);

        $this->assertSame($valid, $this->cache->isStillValid($req, $res));
    }
}
