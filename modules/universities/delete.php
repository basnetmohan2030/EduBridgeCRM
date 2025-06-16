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
// Fetch university
$stmt = $mysqli->prepare('SELECT logo FROM universities WHERE id = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$stmt->bind_result($logo);
$stmt->fetch();
$stmt->close();
if ($logo && file_exists('../../' . $logo)) {
    @unlink('../../' . $logo);
}
// Delete university
$stmt = $mysqli->prepare('DELETE FROM universities WHERE id = ?');
$stmt->bind_param('s', $id);
$stmt->execute();
$stmt->close();
header('Location: list.php');
exit; 