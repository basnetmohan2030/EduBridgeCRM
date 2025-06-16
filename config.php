<?php
// Database configuration

define('DB_HOST', 'localhost');
define('DB_NAME', 'nexsus');
define('DB_USER', 'root');
define('DB_PASS', ''); // Set your MySQL password if any

define('BASE_URL', 'http://localhost/nexsuscrm/'); // Adjust if your base URL is different

// SMTP configuration for PHPMailer
// Update these with your real SMTP server details
// Example for Gmail: host = 'smtp.gmail.com', port = 587, secure = 'tls'
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_FROM', 'admin@nexsus.com');
define('SMTP_FROM_NAME', 'Nexsus CRM');



