<?php
require_once '../../includes/auth.php';
require_login();
if (current_user_role() !== 'admin') { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';

$id = intval($_GET['id'] ?? 0);
if ($id) {
    // Get counsellor and profile picture
    $stmt = $mysqli->prepare('SELECT accountId, profile_picture FROM counsellor WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($accountId, $profile_picture);
    $stmt->fetch();
    $stmt->close();
    // Delete profile picture file
    if ($profile_picture && file_exists('../../' . $profile_picture)) {
        @unlink('../../' . $profile_picture);
    }
    // Delete counsellor
    $stmt = $mysqli->prepare('DELETE FROM counsellor WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    // Delete user account
    if ($accountId) {
        $stmt = $mysqli->prepare('DELETE FROM user WHERE id = ?');
        $stmt->bind_param('s', $accountId);
        $stmt->execute();
        $stmt->close();
    }
}
header('Location: list.php');
exit; 