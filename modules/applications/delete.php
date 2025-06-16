<?php
require_once '../../includes/auth.php';
require_login();

// Strictly enforce admin-only access
$user_role = current_user_role();
if ($user_role !== 'admin') {
    header('Location: list.php');
    exit('Unauthorized access - Only administrators can delete applications');
}

require_once '../../includes/db.php';

$application_id = $_GET['id'] ?? '';
if (empty($application_id)) {
    header('Location: list.php');
    exit('Invalid application ID');
}

// Delete the application
$stmt = $mysqli->prepare("DELETE FROM applications WHERE id = ?");
$stmt->bind_param('s', $application_id);
$stmt->execute();
$stmt->close();

// Redirect with success message
$_SESSION['success_message'] = 'Application deleted successfully';
header('Location: list.php');
exit; 