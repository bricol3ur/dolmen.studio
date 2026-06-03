<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require __DIR__ . '/config/env.php';

// GET → renvoie un token CSRF frais (appelé par le JS au chargement de la page)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);
    exit;
}

// Seul POST est accepté ensuite
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Rate limiting : 3 envois max par tranche de 10 minutes
$now = time();
if (!isset($_SESSION['mail_log'])) $_SESSION['mail_log'] = [];
$_SESSION['mail_log'] = array_filter($_SESSION['mail_log'], fn($t) => $now - $t < 600);
if (count($_SESSION['mail_log']) >= 3) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Réessayez dans quelques minutes.']);
    exit;
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token invalide. Rechargez la page.']);
    exit;
}

// Sanitize
$name    = trim(strip_tags($_POST['name']    ?? ''));
$email   = trim($_POST['email']              ?? '');
$sujet   = trim(strip_tags($_POST['sujet']   ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));

// Validation
$errors = [];
if (mb_strlen($name) < 2)                       $errors[] = 'Nom invalide.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Adresse email invalide.';
if (mb_strlen($sujet) < 2)                      $errors[] = 'Sujet invalide.';
if (mb_strlen($message) < 10)                   $errors[] = 'Message trop court (10 caractères min).';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Envoi PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(SMTP_USER, MAIL_FROM_NAME);
    $mail->addAddress(MAIL_TO);
    $mail->addReplyTo($email, $name);

    $mail->Subject = '[dolmen.studio] ' . $sujet;
    $mail->isHTML(false);
    $mail->Body = "Nom : $name\nEmail : $email\nSujet : $sujet\n\n$message";

    $mail->send();

    // Invalide le token et log l'envoi
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['mail_log'][] = $now;

    echo json_encode(['success' => true, 'message' => 'Message envoyé. Je vous répondrai sous 48h.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
}
