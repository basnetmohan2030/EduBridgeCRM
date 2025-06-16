<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';

$id = intval($_GET['id'] ?? 0);
if ($id) {
    $stmt = $mysqli->prepare('DELETE FROM inquiries WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}
header('Location: list.php');
exit; 