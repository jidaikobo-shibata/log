<?php

use PHPUnit\Framework\TestCase;
use Jidaikobo\Log;

class LogTest extends TestCase
{
    public function testLog()
    {
        $logger = new Log();
        $this->assertTrue(method_exists($logger, 'init'));
    }
}
