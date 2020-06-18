<?php
/**
 * Slim Framework (https://www.slimframework.com)
 *
 * @license   https://github.com/slimphp/Slim-HttpCache/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\HttpCache\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\HttpCache\CacheProvider;
use Slim\Psr7\Factory\ResponseFactory;

use function gmdate;
use function strtotime;
use function time;

class CacheProviderTest extends TestCase
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

    public function testAllowCacheWithInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Cache-Control type. Must be "public" or "private".');

        $cacheProvider = new CacheProvider();
        $cacheProvider->allowCache($this->createResponse(), 'unknown');
    }

    public function testAllowCacheWithMaxAgeAsString()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->allowCache($this->createResponse(), 'private', '+30 seconds');

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('private, max-age=30', $cacheControl);
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

    public function testWithExpiresTimeAsString()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withExpires($this->createResponse(), '+30 seconds');
        $time = strtotime('+30 seconds');

        $expires = $res->getHeaderLine('Expires');
        $this->assertEquals(gmdate('D, d M Y H:i:s T', $time), $expires);
    }

    public function testWithExpiresTimeAsInvalidString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration value could not be parsed with `strtotime()`.');

        $cacheProvider = new CacheProvider();
        $cacheProvider->withExpires($this->createResponse(), 'this-is-not-a-valid-datetime');
    }

    public function testWithETag()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag($this->createResponse(), $etag);

        $etagHeader = $res->getHeaderLine('ETag');

        $this->assertEquals('"'.$etag.'"', $etagHeader);
    }

    public function testWithETagWeak()
    {
        $etag = 'abc';
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withEtag($this->createResponse(), $etag, 'weak');

        $etagHeader = $res->getHeaderLine('ETag');

        $this->assertEquals('W/"'.$etag.'"', $etagHeader);
    }

    public function testWithETagInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);

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

    public function testWithLastModifiedTimeAsString()
    {
        $cacheProvider = new CacheProvider();
        $res = $cacheProvider->withLastModified($this->createResponse(), '+30 seconds');
        $time = strtotime('+30 seconds');

        $lastModified = $res->getHeaderLine('Last-Modified');

        $this->assertEquals(gmdate('D, d M Y H:i:s T', $time), $lastModified);
    }

    public function testWithLastModifiedTimeAsInvalidString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Last Modified value could not be parsed with `strtotime()`.');

        $cacheProvider = new CacheProvider();
        $cacheProvider->withLastModified($this->createResponse(), 'this-is-not-a-valid-datetime');
    }
}
