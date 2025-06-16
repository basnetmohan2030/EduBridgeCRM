<?php
require_once '../../includes/auth.php';
require_login();

// Only admin can delete
$user_role = htmlspecialchars(current_user_role());
if ($user_role !== 'admin') {
    header('Location: list.php');
    exit('Unauthorized access');
}

require_once '../../includes/db.php';

$client_id = intval($_GET['id'] ?? 0);
if ($client_id <= 0) {
    header('Location: list.php');
    exit('Invalid client ID');
}

// Delete the client
$stmt = $mysqli->prepare("DELETE FROM clients WHERE id = ?");
$stmt->bind_param('i', $client_id);
$stmt->execute();
$stmt->close();

header('Location: list.php');
exit; 