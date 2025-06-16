<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
$id = intval($_GET['id'] ?? 0);
$client_id = intval($_GET['client_id'] ?? 0);
if ($id && $client_id) {
    $stmt = $mysqli->prepare('DELETE FROM student_test_scores WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}
header('Location: view.php?id=' . $client_id . '&tab=scores');
exit; 