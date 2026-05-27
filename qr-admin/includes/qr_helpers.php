<?php
function detect_device(string $user_agent): string
{
    $ua = strtolower($user_agent);
    if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
        return 'tablet';
    }
    if (str_contains($ua, 'iphone') || str_contains($ua, 'android') || str_contains($ua, 'mobile')) {
        return 'mobile';
    }
    return 'desktop';
}

function generate_short_code(PDO $db): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    for ($i = 0; $i < 10; $i++) {
        $code = '';
        for ($j = 0; $j < 6; $j++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $db->prepare('SELECT id FROM qr_codes WHERE short_code = ?');
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
    }
    throw new RuntimeException('Could not generate a unique short code after 10 attempts.');
}

function get_city(string $ip): string
{
    $url      = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=city&lang=hu';
    $response = @file_get_contents($url);
    if ($response === false) {
        return 'Ismeretlen';
    }
    $data = json_decode($response, true);
    return $data['city'] ?? 'Ismeretlen';
}
