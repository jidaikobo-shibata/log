<?php

namespace jidaikobo;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use InvalidArgumentException;

/**
 * LogPsr3 class
 *
 * Provides logging functional.
 *
 * This class adheres to PSR-1 and PSR-12 coding standards and implements
 * PSR-3 (Logger Interface) for compatibility with PSR-3 consumers.
 *
 * @package   jidaikobo/log
 * @author    jidaikobo-shibata
 * @license   Unlicense
 */
class LogPsr3 implements LoggerInterface
{
    private string $logFile;
    private int $maxFileSize;

    public function __construct(string $logFile, int $maxFileSize)
    {
        $this->logFile = $logFile;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Register error and exception handlers.
     */
    public function registerHandlers(): void
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * Handle PHP errors and log them.
     *
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int $errline The line number the error was raised at.
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
     */
    public function exceptionHandler(\Throwable $exception): void
    {
        $errorMessage = "Uncaught Exception: {$exception->getMessage()} in {$exception->getFile()} on line {$exception->getLine()}";
        $this->write($errorMessage, 'ERROR');
    }

    /**
     * Rotate the log file if it exceeds the maximum size.
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
     * @param string|array $message The message to log.
     * @param string $level The log level (e.g., INFO, WARNING, DEBUG, ERROR).
     * @return void
     */
    public function write(string|array $message, string $level = 'INFO'): void
    {
        $this->rotateLogFile();

        // Ensure the log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Format the message
        if (is_object($message)) {
            $message = method_exists($message, '__toString') ? (string) $message : json_encode($message, JSON_PRETTY_PRINT);
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
     * @param mixed $level The log level.
     * @param string|\Stringable $message The log message.
     * @param array $context Context array.
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

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $traceInfo = array_map(function ($trace) {
            $file = $trace['file'] ?? '[internal function]';
            $line = $trace['line'] ?? '?';
            $function = $trace['function'] ?? '?';
            $class = $trace['class'] ?? '';
            return "$file:$line - {$class}{$function}()";
        }, $backtrace);

        $message .= "\nTrace:\n" . implode("\n", $traceInfo);

        $this->write($message, 'DEBUG');
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message The message with placeholders.
     * @param array $context The context array.
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
