swagger-header-middleware
==============

PHP middleware that tries to infer outbound content types and attach appropriate headers.

[![Build Status](https://travis-ci.org/avalanche-development/swagger-header-middleware.svg?branch=master)](https://travis-ci.org/avalanche-development/swagger-header-middleware)
[![Code Climate](https://codeclimate.com/github/avalanche-development/swagger-header-middleware/badges/gpa.svg)](https://codeclimate.com/github/avalanche-development/swagger-header-middleware)
[![Test Coverage](https://codeclimate.com/github/avalanche-development/swagger-header-middleware/badges/coverage.svg)](https://codeclimate.com/github/avalanche-development/swagger-header-middleware/coverage)

## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install swagger-header-middleware.

```bash
$ composer require avalanche-development/swagger-header-middleware
```

swagger-header-middleware requires PHP 5.6 or newer.

## Usage

This middleware depends on [swagger-router-middleware](https://github.com/avalanche-development/swagger-router-middleware) to have a resolved swagger attribute attached to the request object. If it is not found, then all modifications to the response object are skipped. If it is, it will attempt to attach outbound content types.

```php
$header = new AvalancheDevelopment\SwaggerHeaderMiddleware\Header;
$result = $header($request, $response, $next); // middleware signature
```

It is recommended that this is one of the top items in the stack, soon after swagger-router-middleware, and ensure that any sort of header validation is done after this is executed.

### Interface

This middleware implements LoggerAwareInterface, so feel free to attach your logger for all that logging goodness.

```php
$header = new AvalancheDevelopment\SwaggerHeaderMiddleware\Header;
$header->setLogger($logger);
... etc
```

Again, it depends on a swagger attribute being in the request. If a request object is passed in without it, then everything is skipped. Values are resolved based on the operation and global settings within the spec.

```php
$swagger = $request->getAttribute('swagger');
var_dump($swagger);
...
[
  'produces' => [
    'application/json',
  ],
  'consumes' => [
    'application/json',
  ],
]
```

If a json string is passed in, then this middleware will automatically attach a `application/json` content-type header... only if a header is not already provided. To override this overreaching behavior, simply attach your own header before this is hit.

## Development

This library is in active development. Some things are not yet supported (such as detecting non-json header types).

### Tests

To execute the test suite, you'll need phpunit (and to install package with dev dependencies).

```bash
$ phpunit
```

## License

swagger-header-middleware is licensed under the MIT license. See [License File](LICENSE) for more information.
