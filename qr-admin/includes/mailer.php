<?php
function send_password_reminder(string $password): bool
{
    $subject = 'QR Admin – Jelszó emlékeztető';
    $message = "A jelenlegi QR Admin jelszava: {$password}";
    $headers = 'From: noreply@somfaiklimatechnika.hu' . "\r\n" .
               'Content-Type: text/plain; charset=UTF-8';

    return mail(ADMIN_EMAIL, $subject, $message, $headers);
}
