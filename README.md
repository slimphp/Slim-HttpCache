# Slim Framework HTTP Cache

[![Build Status](https://github.com/slimphp/Slim-HttpCache/actions/workflows/tests.yml/badge.svg?branch=1.x)](https://github.com/slimphp/Slim-HttpCache/actions)
[![Coverage Status](https://coveralls.io/repos/github/slimphp/Slim-HttpCache/badge.svg?branch=1.x)](https://coveralls.io/github/slimphp/Slim-HttpCache?branch=1.x)
[![Latest Stable Version](https://poser.pugx.org/slim/http-cache/v)](https://packagist.org/packages/slim/http-cache)
[![License](https://poser.pugx.org/slim/http-cache/license)](https://packagist.org/packages/slim/http-cache)

This repository contains a Slim Framework HTTP cache middleware and service provider.

## Install

Via Composer

```bash
$ composer require slim/http-cache
```

Requires Slim 4.0.0 or newer.

## Usage

```php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__.'/../vendor/autoload.php';

$app = \Slim\Factory\AppFactory::create();

// Register the http cache middleware.
$app->add(new \Slim\HttpCache\Cache('public', 86400));

// Create the cache provider.
$cacheProvider = new \Slim\HttpCache\CacheProvider();

// Register a route and let the closure callback inherit the cache provider.
$app->get(
    '/',
    function (Request $request, Response $response, array $args) use ($cacheProvider): Response {
        // Use the cache provider.
        $response = $cacheProvider->withEtag($response, 'abc');

        $response->getBody()->write('Hello world!');

        return $response;
    }
);

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
