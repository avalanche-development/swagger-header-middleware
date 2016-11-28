swagger-header-middleware
==============

PHP middleware that validates content headers, enforces based on [swagger](http://swagger.io/) spec, and may try to infer outbound content types.

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

This middleware depends on [swagger-router-middleware](https://github.com/avalanche-development/swagger-router-middleware) to have a resolved swagger attribute attached to the request object. If it is not found, then all header checks are skipped. If it is, then it will use the produces/consumes keys to check incoming content, attach outbound content types, check outgoing content, and also validate against the request accept fields. If any validation fails than an appropriate [peel](https://github.com/avalanche-development/peel) exception is thrown.

```php
$header = new AvalancheDevelopment\SwaggerHeaderMiddleware\Header;
$result = $header($request, $response, $next); // middleware signature
```

It is recommended that this is one of the top items in the stack, after swagger-router-middleware, as to reject bad requests as soon as possible and perform response validations after any other modifying middleware is done.

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

Two different http errors may be thrown from this middleware.

# 406 Not Acceptable - when the client's content-type or accept headers don't match swagger spec
# 500 Internal Server Error - when the app's outbound content fails swagger validation

Finally, if a json string is passed in, then this middleware will automatically attach a `application/json` content-type header... only if a header is not already provided. To override this overreaching behavior, simply attach your own header before this is hit.

## Development

This library is in active development. Some things are not yet supported (such as wildcard content-types).

### Tests

To execute the test suite, you'll need phpunit (and to install package with dev dependencies).

```bash
$ phpunit
```

## License

swagger-header-middleware is licensed under the MIT license. See [License File](LICENSE) for more information.
