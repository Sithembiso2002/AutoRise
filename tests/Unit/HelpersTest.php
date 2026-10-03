<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class HelpersTest extends TestCase
{
    public function testEscape(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
        $this->assertSame('&amp;&quot;', e('&"'));
    }

    public function testInitialsOf(): void
    {
        $this->assertSame('SS', initials_of('Sethembiso S.'));
        $this->assertSame('TM', initials_of('Thato M.'));
        $this->assertSame('U',  initials_of(''));
        $this->assertSame('A',  initials_of('Alice'));
        $this->assertSame('AB', initials_of('  Alice   Brown  '));
    }

    public function testUrlBuildsRelativeToAppUrl(): void
    {
        $this->assertStringEndsWith('/products', url('products'));
        $this->assertStringEndsWith('/login', url('/login'));
    }

    public function testAssetReturnsCssPath(): void
    {
        $this->assertStringContainsString('/assets/css/app.css', asset('css/app.css'));
    }

    public function testProductImageFallback(): void
    {
        $url = product_image(null, 'Test Product');
        $this->assertStringContainsString('placeholder', $url);
    }
}