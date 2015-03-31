<?php
namespace Slim\HttpCache\Tests;

use Slim\HttpCache\Cache;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Body;
use Slim\Collection;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    public function requestFactory()
    {
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = new Collection();
        $serverParams = new Collection();
        $body = new Body(fopen('php://temp', 'r+'));

        return new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
    }

    public function testCacheControlHeader()
    {
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory();
        $res = new Response();
        $next = function (Request $req, Response $res) {
            return $res;
        };
        $res = $cache($req, $res, $next);

        $this->assertEquals('public, max-age=86400', $res->getHeader('Cache-Control'));
    }

    public function testCacheControlHeaderDoesNotOverrideExistingHeader()
    {
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory();
        $res = new Response();
        $next = function (Request $req, Response $res) {
            return $res->withHeader('Cache-Control', 'no-cache,no-store');
        };
        $res = $cache($req, $res, $next);

        $this->assertEquals('no-cache,no-store', $res->getHeader('Cache-Control'));
    }

    public function testLastModifiedWithCacheHit()
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now + 86400);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now + 86400);
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-Modified-Since', $ifModifiedSince);
        $res = new Response();
        $next = function (Request $req, Response $res) use ($lastModified) {
            return $res->withHeader('Last-Modified', $lastModified);
        };
        $res = $cache($req, $res, $next);

        $this->assertEquals(304, $res->getStatusCode());
    }

    public function testLastModifiedWithCacheHitAndNewerDate()
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now + 86400);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now + 172800); // <-- Newer date
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-Modified-Since', $ifModifiedSince);
        $res = new Response();
        $next = function (Request $req, Response $res) use ($lastModified) {
            return $res->withHeader('Last-Modified', $lastModified);
        };
        $res = $cache($req, $res, $next);

        $this->assertEquals(304, $res->getStatusCode());
    }

    public function testLastModifiedWithCacheHitAndOlderDate()
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now + 86400);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now); // <-- Older date
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-Modified-Since', $ifModifiedSince);
        $res = new Response();
        $next = function (Request $req, Response $res) use ($lastModified) {
            return $res->withHeader('Last-Modified', $lastModified);
        };
        $res = $cache($req, $res, $next);

        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testLastModifiedWithCacheMiss()
    {
        $now = time();
        $lastModified = gmdate('D, d M Y H:i:s T', $now + 86400);
        $ifModifiedSince = gmdate('D, d M Y H:i:s T', $now - 86400);
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-Modified-Since', $ifModifiedSince);
        $res = new Response();
        $next = function (Request $req, Response $res) use ($lastModified) {
            return $res->withHeader('Last-Modified', $lastModified);
        };
        $res = $cache($req, $res, $next);

        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testETagWithCacheHit()
    {
        $etag = 'abc';
        $ifNoneMatch = 'abc';
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-None-Match', $ifNoneMatch);
        $res = new Response();
        $next = function (Request $req, Response $res) use ($etag) {
            return $res->withHeader('ETag', $etag);
        };
        $res = $cache($req, $res, $next);

        $this->assertEquals(304, $res->getStatusCode());
    }

    public function testETagWithCacheMiss()
    {
        $etag = 'abc';
        $ifNoneMatch = 'xyz';
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory()->withHeader('If-None-Match', $ifNoneMatch);
        $res = new Response();
        $next = function (Request $req, Response $res) use ($etag) {
            return $res->withHeader('ETag', $etag);
        };
        $res = $cache($req, $res, $next);

        $this->assertEquals(200, $res->getStatusCode());
    }
}
