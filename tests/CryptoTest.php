<?php
use PHPUnit\Framework\TestCase;

if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', 'TestKey_32BytesLong_ForUnitTests!');
}
require_once __DIR__ . '/../qr-admin/includes/crypto.php';

class CryptoTest extends TestCase
{
    public function test_encrypt_then_decrypt_returns_original(): void
    {
        $this->assertSame('password1', aes_decrypt(aes_encrypt('password1')));
    }

    public function test_encrypt_produces_different_ciphertext_each_time(): void
    {
        $this->assertNotSame(aes_encrypt('password1'), aes_encrypt('password1'));
    }

    public function test_output_is_valid_base64(): void
    {
        $this->assertNotFalse(base64_decode(aes_encrypt('test'), true));
    }
}
