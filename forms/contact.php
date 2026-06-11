<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$receivingEmailAddress = 'contact@example.com';
$allowedMethods = ['POST'];

if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', $allowedMethods, true)) {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

if (!empty($_POST['website'])) {
    echo 'OK';
    exit;
}

function clean_input(string $value): string
{
    $value = trim($value);
    $value = str_replace(["\r", "\n"], ' ', $value);
    return preg_replace('/\s+/', ' ', $value) ?? '';
}

function fail(string $message, int $status = 400): void
{
    http_response_code($status);
    echo $message;
    exit;
}

$name = clean_input((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = clean_input((string) ($_POST['subject'] ?? ($_POST['sbject'] ?? '')));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    fail('Please complete all required fields.');
}

if (mb_strlen($name) > 80 || mb_strlen($email) > 120 || mb_strlen($subject) > 150 || mb_strlen($message) > 5000) {
    fail('One or more fields are too long.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please enter a valid email address.');
}

if (!filter_var($receivingEmailAddress, FILTER_VALIDATE_EMAIL)) {
    fail('Contact form is not configured yet.', 500);
}

if (!function_exists('mail')) {
    fail('Email service is unavailable right now.', 500);
}

$safeSubject = '[Website Contact] ' . $subject;
$safeMessage = implode("\n", [
    'Name: ' . $name,
    'Email: ' . $email,
    '',
    'Message:',
    $message,
]);

$headers = [
    'From: ' . $receivingEmailAddress,
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
];

$mailSent = mail(
    $receivingEmailAddress,
    $safeSubject,
    $safeMessage,
    implode("\r\n", $headers)
);

if (!$mailSent) {
    fail('Unable to send your message right now. Please try again later.', 500);
}

echo 'OK';
