<?php

namespace jidaikobo;

use jidaikobo\LogPsr3;

/**
 * Log class (Static Wrapper for LogPsr3)
 *
 * Provides a static interface for the PSR-3 compliant LogPsr3 class.
 *
 * This class allows developers to use logging functionality via static
 * methods while maintaining the flexibility of the LogPsr3 instance-based design.
 *
 * @package   jidaikobo/log
 * @author    jidaikobo-shibata
 * @license   Unlicense
 */
class Log
{
    private static ?LogPsr3 $instance = null;

    /**
     * Initialize the Logger instance.
     *
     * @param string $logFile
     * @param int $maxFileSize
     * @return void
     */
    public static function init(string $logFile = null, int $maxFileSize = 10 * 1024 * 1024): void
    {
        if ($logFile === null) {
            $logFile = dirname(__DIR__, 4) . '/logs/php.log'; // default project root
        }

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        self::$instance = new LogPsr3($logFile, $maxFileSize);
    }

    /**
     * Get the Logger instance.
     *
     * @return LogPsr3
     * @throws \RuntimeException if the instance is not initialized.
     */
    public static function getInstance(): LogPsr3
    {
        if (!self::$instance) {
            self::init();
        }

        return self::$instance;
    }

    /**
     * Forward static calls to the Logger instance.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        $instance = self::getInstance();

        if (!method_exists($instance, $method)) {
            $availableMethods = implode(', ', get_class_methods($instance));
            throw new \BadMethodCallException("Method '{$method}' does not exist on LogPsr3. Available methods: {$availableMethods}");
        }

        return $instance->$method(...$args);
    }
}
