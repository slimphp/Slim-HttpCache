# Slim Framework HTTP Cache

[![Build Status](https://travis-ci.org/slimphp/Slim-HttpCache.svg?branch=master)](https://travis-ci.org/slimphp/Slim-HttpCache)

This repository contains a Slim Framework HTTP cache middleware and service provider.

## Install

Via Composer

``` bash
$ composer require slim/http-cache
```

Requires Slim 3.0.0 or newer.

## Usage

```php
$app = new \Slim\App();

// Register middleware to automatically set default cache headers
$app->add(new \Slim\Middleware\HttpCache\Cache('public', 86400));

// Fetch DI Container
$container = $app->getContainer();

// Register service provider to get access to the cache methods
$container->register(new \Slim\Middleware\HttpCache\CacheProvider);

// Example route with ETag header
$app->get('/foo', function ($req, $res, $args) {
    $resWithEtag = $this->cache->withEtag($res, 'abc');

    // Optional: Return early if the Etag matches the Etag in the request
    if ($this->cache->isStillValid($req, $resWithEtag)) {
        return $resWithEtag->withStatus(304);
    }

    $resWithEtag->getBody()->write('foo');

    return $resWithEtag;
});

$app->run();
```

## Testing

``` bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@slimframework.com instead of using the issue tracker.

## Credits

- [Josh Lockhart](https://github.com/codeguy)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
