# jidaikobo/log

`jidaikobo/log` is a simple and flexible logging utility that adheres to the PSR-3 (Logger Interface) standard. It provides both instance-based and static methods for logging, making it easy to integrate into any PHP project.

This package was developed to simplify logging in small-scale projects, such as those using the Slim Framework. It also fulfills the need to log arrays efficiently, which can be crucial for debugging.

## Features

- **PSR-3 compliant**: Supports all standard logging levels.
- **Instance-based design**: Fully compatible with dependency injection and modern PHP practices.
- **Static wrapper**: Offers convenient static methods for quick and simple logging.
- **Log file rotation**: Automatically rotates log files when they reach a specified size.
- **Error and exception handling**: Integrates with PHP error and exception handling mechanisms.

## Installation

Install the package via Composer:

```bash
composer require jidaikobo/log
```

## Usage

### Basic Initialization

Initialize the logger with a custom log file path and maximum file size (optional):

```php
use jidaikobo\Log;
Log::init('/path/to/log.log', 5 * 1024 * 1024); // 5MB max file size
```

If Log::init() is not explicitly called, the logger will use default settings.

### Logging Messages

Log messages at various levels using static methods:

```php
Log::info('This is an informational message.');
Log::warning('This is a warning message.');
Log::error('An error occurred.');
Log::debug('Debugging details.');
```

To output an array:

```php
Log::write(['foo', 'bar', 'baz']);
```

### Error and Exception Handling

Register the logger to handle PHP errors and uncaught exceptions:

```php
Log::getInstance()->registerHandlers();
```

## Requirements

- PHP >= 7.4

## License

This project is licensed under the [Unlicense](https://unlicense.org/).

## Author

- [jidaikobo-shibata](https://github.com/jidaikobo-shibata/)

## Link

- [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/)
