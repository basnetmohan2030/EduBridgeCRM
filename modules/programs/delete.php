<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) {
    header('Location: list.php');
    exit;
}
require_once '../../includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: list.php');
    exit;
}
// Delete program
$stmt = $mysqli->prepare('DELETE FROM programs WHERE id = ?');
$stmt->bind_param('s', $id);
$stmt->execute();
$stmt->close();
header('Location: list.php');
exit; 