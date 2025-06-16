<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
if (!defined('BASE_URL')) require_once __DIR__ . '/../config.php';

function send_user_credentials($to, $name, $email, $password) {
    $mail = new PHPMailer(true);
    try {
        // SMTP config from config.php
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE; // 'tls' or 'ssl'
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Your EduBridge CRM Login Credentials';
        $mail->Body = '<p>Dear ' . htmlspecialchars($name) . ',</p>' .
            '<p>Your account has been created. Please use the following credentials to log in:</p>' .
            '<ul>' .
            '<li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>' .
            '<li><strong>Password:</strong> ' . htmlspecialchars($password) . '</li>' .
            '</ul>' .
            '<p><a href="' . BASE_URL . 'login.php">Login here</a></p>' .
            '<p>Thank you,<br>EduBridge CRM Team</p>';
        $mail->AltBody = "Dear $name,\nYour account has been created. Email: $email Password: $password";
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Optionally log $mail->ErrorInfo
        return false;
    }
}

function test_smtp($to) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'SMTP Test - EduBridge CRM';
        $mail->Body = '<p>This is a test email from EduBridge CRM SMTP configuration.</p>';
        $mail->AltBody = 'This is a test email from EduBridge CRM SMTP configuration.';
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
} 