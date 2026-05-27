<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../qr-admin/includes/qr_helpers.php';

class QrHelpersTest extends TestCase
{
    public function test_iphone_is_mobile(): void
    {
        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
        $this->assertSame('mobile', detect_device($ua));
    }

    public function test_ipad_is_tablet(): void
    {
        $ua = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
        $this->assertSame('tablet', detect_device($ua));
    }

    public function test_windows_chrome_is_desktop(): void
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36';
        $this->assertSame('desktop', detect_device($ua));
    }

    public function test_android_mobile_is_mobile(): void
    {
        $ua = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/124.0 Mobile Safari/537.36';
        $this->assertSame('mobile', detect_device($ua));
    }
}
