<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$phone || !$subject) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$to      = 'info@somfaiklimatechnika.hu';
$subjectLine = '=?UTF-8?B?' . base64_encode('Új üzenet: ' . $subject) . '?=';

$body  = "Feladó: {$name}\n";
$body .= "Telefon: {$phone}\n";
if ($email) $body .= "E-mail: {$email}\n";
$body .= "Tárgy: {$subject}\n\n";
$body .= $message ? "Üzenet:\n{$message}\n" : '';

$headers  = 'From: noreply@somfaiklimatechnika.hu' . "\r\n";
$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
if ($email) {
    $headers .= 'Reply-To: ' . $email . "\r\n";
}

$sent = mail($to, $subjectLine, $body, $headers);

echo json_encode(['ok' => $sent]);
