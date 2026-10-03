<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\RateLimiter;
use Tests\TestCase;

final class RateLimiterTest extends TestCase
{
    /* No setUp() override needed — the base TestCase
       creates the schema once and wipes data between tests. */

    public function testFirstHitAllowed(): void
    {
        $r = RateLimiter::check('test-bucket', 'ip:1.2.3.4', 5, 60);
        $this->assertTrue($r['allowed']);
        $this->assertSame(4, $r['remaining']);
    }

    public function testBlocksAfterLimit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $r = RateLimiter::check('test-bucket', 'ip:1.2.3.4', 5, 60);
            $this->assertTrue($r['allowed'], "Hit $i should be allowed");
        }

        $r = RateLimiter::check('test-bucket', 'ip:1.2.3.4', 5, 60);
        $this->assertFalse($r['allowed'], 'Sixth hit must be blocked');
        $this->assertGreaterThan(0, $r['retry_after']);
    }

    public function testDifferentIdentifiersAreIndependent(): void
    {
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::check('test-bucket', 'ip:1.1.1.1', 5, 60);
        }
        $r = RateLimiter::check('test-bucket', 'ip:1.1.1.1', 5, 60);
        $this->assertFalse($r['allowed']);

        $r = RateLimiter::check('test-bucket', 'ip:2.2.2.2', 5, 60);
        $this->assertTrue($r['allowed'], 'Different identifier should have its own counter');
    }

    public function testClearResetsBucket(): void
    {
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::check('test-bucket', 'ip:1.2.3.4', 5, 60);
        }
        RateLimiter::clear('test-bucket', 'ip:1.2.3.4');

        $r = RateLimiter::check('test-bucket', 'ip:1.2.3.4', 5, 60);
        $this->assertTrue($r['allowed']);
    }

    public function testWindowExpiryAllowsNewHits(): void
    {
        RateLimiter::check('short-window', 'ip:x', 1, 1); // 1-second window
        sleep(2);
        $r = RateLimiter::check('short-window', 'ip:x', 1, 1);
        $this->assertTrue($r['allowed']);
    }
}