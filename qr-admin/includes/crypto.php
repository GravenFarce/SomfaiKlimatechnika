<?php
function aes_encrypt(string $plaintext): string
{
    $iv = random_bytes(16);
    return base64_encode($iv . openssl_encrypt($plaintext, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv));
}

function aes_decrypt(string $ciphertext): string
{
    $data = base64_decode($ciphertext);
    return openssl_decrypt(substr($data, 16), 'AES-256-CBC', ENCRYPTION_KEY, 0, substr($data, 0, 16));
}
