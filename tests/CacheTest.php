<?php
namespace Slim\Middleware\HttpCache\Tests;

use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Middleware\HttpCache\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    public function requestFactory()
    {
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
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

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('public, max-age=86400', $cacheControl);
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

        $cacheControl = $res->getHeaderLine('Cache-Control');

        $this->assertEquals('no-cache,no-store', $cacheControl);
    }

    public function testCacheControlHeaderIgnoresUnsafeMethods()
    {
        $cache = new Cache('public', 86400);
        $req = $this->requestFactory();
        $req = $req->withMethod('POST');

        $res = new Response();
        $next = function (Request $req, Response $res) {
            return $res;
        };
        $res = $cache($req, $res, $next);

        $this->assertFalse($res->hasHeader('Cache-Control'));
    }

    public function testSetsStatusTo304IfCacheStillValid()
    {
        $cache = $this->getMock('Slim\Middleware\HttpCache\CacheHelper');
        $cache->expects($this->once())->method('isStillValid')->will($this->returnValue(true));

        $middleware = new Cache('public', 86400, $cache);
        $req = $this->requestFactory();

        $res = new Response();
        $res = $res->withHeader('Cache-Control', 'public');
        $next = function (Request $req, Response $res) {
            return $res;
        };
        $res = $middleware($req, $res, $next);

        $this->assertSame(304, $res->getStatusCode());
    }
}
