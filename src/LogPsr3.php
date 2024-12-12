<?php

/**
 * LogPsr3 class
 *
 * Provides logging functional.
 *
 * This class adheres to PSR-1 and PSR-12 coding standards and implements
 * PSR-3 (Logger Interface) for compatibility with PSR-3 consumers.
 *
 * @category Utility
 * @package  jidaikobo/log
 * @author   jidaikobo-shibata <shibata@jidaikobo.com>
 * @license  Unlicense <https://unlicense.org/>
 * @link     https://github.com/jidaikobo-shibata/log
 */

namespace jidaikobo;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use InvalidArgumentException;

class LogPsr3 implements LoggerInterface
{
    private string $logFile;
    private int $maxFileSize;

    /**
     * Determine the location of the log file and the size at which the log should be rotated.
     *
     * @param string $logFile     path of log file
     * @param int    $maxFileSize log rotation size
     *
     * @return void
     */
    public function __construct(string $logFile, int $maxFileSize)
    {
        $this->logFile = $logFile;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Register error and exception handlers.
     *
     * @return void
     */
    public function registerHandlers(): void
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * Handle PHP errors and log them.
     *
     * @param int    $errno   The level of the error raised.
     * @param string $errstr  The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int    $errline The line number the error was raised at.
     *
     * @return bool Always returns false to allow PHP's default error handler.
     */
    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $errorMessage = "PHP Error [Level $errno]: $errstr in $errfile on line $errline";
        $this->write($errorMessage, 'ERROR');
        return false;
    }

    /**
     * Handle uncaught exceptions and log them.
     *
     * @param \Throwable $exception The uncaught exception.
     *
     * @return void
     */
    public function exceptionHandler(\Throwable $exception): void
    {
        $errorMessage = sprintf(
            "Uncaught Exception: %s in %s on line %d",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
    }

    /**
     * Rotate the log file if it exceeds the maximum size.
     *
     * @return void
     */
    private function rotateLogFile(): void
    {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            rename($this->logFile, $this->logFile . '.' . time());
        }
    }

    /**
     * Write a message to the log file.
     *
     * @param string|array<string|int, string>|object $message The message to log.
     * @param string                                  $level   The log level.
     *
     * @return void
     */
    public function write(string|array|object $message, string $level = 'INFO'): void
    {
        $this->rotateLogFile();

        // Ensure the log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Format the message
        if (is_object($message)) {
            if (method_exists($message, '__toString')) {
                $message = (string) $message;
            } else {
                $message = json_encode($message, JSON_PRETTY_PRINT);
            }
        } elseif (is_array($message)) {
            $message = var_export($message, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;

        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed                $level   The log level.
     * @param string|\Stringable   $message The log message.
     * @param array<string, mixed> $context Context array.
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        if (!in_array($level, $this->getLogLevels(), true)) {
            throw new InvalidArgumentException("Invalid log level: $level");
        }

        $message = $this->interpolate($message, $context);
        $this->write($message, strtoupper($level));
    }

    /**
     * Logs a emergency message.
     *
     * @param string $message               The message to log.
     * @param array<string, mixed> $context Additional context for the log message.
     *
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Logs a alert message.
     *
     * @param string $message               The message to log.
     * @param array<string, mixed> $context Additional context for the log message.
     *
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Logs a critical message.
     *
     * @param string $message               The message to log.
     * @param array<string, mixed> $context Additional context for the log message.
     *
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Logs a error message.
     *
     * @param string $message               The message to log.
     * @param array<string, mixed> $context Additional context for the log message.
     *
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Logs a warning message.
     *
     * @param string $message               The message to log.
     * @param array<string, mixed> $context Additional context for the log message.
     *
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Logs a notice message.
     *
     * @param string $message               The message to log.
     * @param array<string, mixed> $context Additional context for the log message.
     *
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Logs a info message.
     *
     * @param string $message               The message to log.
     * @param array<string, mixed> $context Additional context for the log message.
     *
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Logs a debug message.
     *
     * @param string $message               The message to log.
     * @param array<string, mixed> $context Additional context for the log message.
     *
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $traceInfo = array_map(
            function ($trace) {
                $file = $trace['file'] ?? '[internal function]';
                $line = $trace['line'] ?? '?';
                $function = $trace['function'];
                $class = $trace['class'] ?? '';
                return "$file:$line - {$class}{$function}()";
            },
            $backtrace
        );

        $message .= "\nTrace:\n" . implode("\n", $traceInfo);

        $this->write($message, 'DEBUG');
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message               The message with placeholders.
     * @param array<string, mixed> $context The context array.
     *
     * @return string The interpolated message.
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements['{' . $key . '}'] = $value;
        }

        return strtr($message, $replacements);
    }

    /**
     * Get supported log levels.
     *
     * @return string[]
     */
    private function getLogLevels(): array
    {
        return [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];
    }
}
