<?php
namespace Slim\Middleware\HttpCache\Tests;

use Pimple\Container;
use Slim\Middleware\HttpCache\CacheProvider;

class CacheProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Slim\Middleware\HttpCache\CacheProvider::register
     */
    public function testRegister()
    {
        $container = new Container();
        $container->register(new CacheProvider());

        $this->assertTrue($container->offsetExists('cache'));
        $this->assertInstanceOf('Slim\Middleware\HttpCache\CacheHelper', $container->offsetGet('cache'));
    }
}
