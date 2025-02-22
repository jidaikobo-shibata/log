<?php

use Jidaikobo\LogPsr3;
use Psr\Log\LogLevel;
use PHPUnit\Framework\TestCase;

class LogPsr3Test extends TestCase
{
    private string $testLogFile;
    private LogPsr3 $logger;

    protected function setUp(): void
    {
        // テスト用のログファイル
        $this->testLogFile = __DIR__ . '/test.log';

        // ログファイルが存在する場合削除
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }

        // ロガーのインスタンスを作成（ログサイズ制限: 1000バイト）
        $this->logger = new LogPsr3($this->testLogFile, 1000);
    }

    protected function tearDown(): void
    {
        // テスト後にログファイルを削除
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    public function testWriteCreatesLogFile()
    {
        $this->logger->write("Test log message");

        // ログファイルが作成されたことを確認
        $this->assertFileExists($this->testLogFile);

        // ログファイルの内容をチェック
        $logContents = file_get_contents($this->testLogFile);
        $this->assertStringContainsString("Test log message", $logContents);
    }

    public function testLogMethodWritesToFile()
    {
        $this->logger->log(LogLevel::INFO, "Info log");

        // ログファイルの内容をチェック
        $logContents = file_get_contents($this->testLogFile);
        $this->assertStringContainsString("INFO", $logContents);
        $this->assertStringContainsString("Info log", $logContents);
    }

    public function testLoggingDifferentLevels()
    {
        $this->logger->error("Error log");
        $this->logger->debug("Debug log");

        $logContents = file_get_contents($this->testLogFile);
        $this->assertStringContainsString("ERROR", $logContents);
        $this->assertStringContainsString("Error log", $logContents);
        $this->assertStringContainsString("DEBUG", $logContents);
        $this->assertStringContainsString("Debug log", $logContents);
    }

    public function testRotateLogFile()
    {
        // 事前にログファイルを作成し、制限サイズを超えた状態にする
        file_put_contents($this->testLogFile, str_repeat("A", 1100));

        // ログを書き込むことでローテーションが発生するか確認
        $this->logger->write("New log message");

        // 新しいログファイルが作成されているか確認
        $this->assertFileExists($this->testLogFile);
        $this->assertFileExists($this->testLogFile . '.' . time());

        // 新しいログファイルには "New log message" が含まれることを確認
        $logContents = file_get_contents($this->testLogFile);
        $this->assertStringContainsString("New log message", $logContents);
    }

    public function testErrorHandlerLogsErrors()
    {
        // 現在のエラーハンドラーを保存
        $previousHandler = set_error_handler([$this->logger, 'errorHandler']);

        // 意図的に未定義変数を使用（E_NOTICE のエラーを発生）
        @$undefinedVar++;

        // ログファイルの内容を確認
        $logContents = file_get_contents($this->testLogFile);
        $this->assertStringContainsString("PHP Error", $logContents);

        // 元のエラーハンドラーを復元
        restore_error_handler();
    }

    public function testExceptionHandlerLogsExceptions()
    {
        // 現在の例外ハンドラーを保存
        $previousHandler = set_exception_handler([$this->logger, 'exceptionHandler']);

        try {
            throw new Exception("Test Exception");
        } catch (Exception $e) {
            $this->logger->exceptionHandler($e);
        }

        // ログファイルの内容を確認
        $logContents = file_get_contents($this->testLogFile);
        $this->assertStringContainsString("Uncaught Exception", $logContents);
        $this->assertStringContainsString("Test Exception", $logContents);

        // 元の例外ハンドラーを復元
        restore_exception_handler();
    }
}
