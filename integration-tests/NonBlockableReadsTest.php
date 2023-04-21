<?php

/**
 * Test case for Exceptions.
 */

declare(strict_types=1);

namespace WebSocket;

use PHPUnit\Framework\TestCase;

class NonBlockingReadsTest extends TestCase
{
    private float $start;

    public function setUp(): void
    {
        $this->start = hrtime(true);
    }

    private function onTerm($_): void {
        // 0.1s
        $this->assertLessThan(100000, hrtime(true) - $this->start);
    }

    public function testClientHasNonBlockableReads(): void
    {
        $pid = pcntl_fork();
        if ($pid) {
            // parent process
            $server = new Server();
            while($server->accept()) {
                $message = $server->receive();
                $server->send("here");
                $this->assertSame('test message 1', $message);
                posix_kill($pid, SIGTERM);
            }

        } else {
            // child process
            pcntl_signal(SIGTERM, [$this, 'onTerm']);
            $client = new Client('ws://0.0.0.0:8000', ['timeout' => 60]);
            $client->send('test message 1');
        }
    }
}
