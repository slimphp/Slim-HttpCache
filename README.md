# Slim Framework HTTP Cache

This repository contains a Slim Framework HTTP cache middleware and service provider.

## Install

Via Composer

``` bash
$ composer require slim/httpcache
```

Requires Slim 3.0.0 or newer.

## Usage

```php
$app = new \Slim\App();

// Register middleware
$app->add(new \Slim\HttpCache\Cache('public', 86400));

// Register service provider
$app->register(new \Slim\HttpCache\CacheProvider);

// Example route with ETag header
$app->get('/foo', function ($req, $res, $args) {
    $resWithEtag = $this['cache']->withEtag($res, 'abc');

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
