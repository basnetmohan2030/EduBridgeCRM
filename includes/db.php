<?php
require_once __DIR__ . '/../config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

// Set charset to utf8mb4 for better Unicode support
$mysqli->set_charset('utf8mb4'); 