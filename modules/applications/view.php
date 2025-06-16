<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
require_once '../../includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    echo '<div class="alert alert-danger">No application ID provided.</div>';
    exit;
}
// Fetch application with related names
$stmt = $mysqli->prepare('SELECT a.*, c.first_name AS client_first, c.last_name AS client_last, co.first_name AS counsellor_first, co.last_name AS counsellor_last, u.name AS university_name, p.name AS program_name FROM applications a JOIN clients c ON a.client_id = c.accountId LEFT JOIN counsellor co ON a.counsellor_id = co.accountId JOIN universities u ON a.university_id = u.id JOIN programs p ON a.program_id = p.id WHERE a.id = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Application - EduBridge CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; }
        .sidebar { min-height: 100vh; background: #e3e9f7; box-shadow: 2px 0 8px rgba(0,0,0,0.03); }
        .sidebar .nav-link { color: #495057; font-weight: 500; border-radius: 0.375rem; margin-bottom: 0.5rem; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #c7d2fe; color: #1d3557; }
        .header { background: #f1f5fa; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .user-info { color: #495057; }
    </style>
</head>
<body>
<?php include '../../includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">View Application</h2>
                <div>
                    <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
                    <?php if ($user_role === 'admin' || ($user_role === 'counsellor' && $app['counsellor_id'] === $_SESSION['user_id'])): ?>
                        <a href="edit.php?id=<?= urlencode($app['id']) ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="delete.php?id=<?= urlencode($app['id']) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this application?');"><i class="bi bi-trash"></i> Delete</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <?php if (!$app): ?>
                        <div class="alert alert-danger">Application not found.</div>
                    <?php else: ?>
                    <h4>Application #<?= htmlspecialchars($app['application_number']) ?></h4>
                    <p><strong>Client:</strong> <?= htmlspecialchars($app['client_first'] . ' ' . $app['client_last']) ?><br>
                    <strong>Counsellor:</strong> <?= $app['counsellor_first'] ? htmlspecialchars($app['counsellor_first'] . ' ' . $app['counsellor_last']) : '<span class="text-muted">N/A</span>' ?><br>
                    <strong>University:</strong> <?= htmlspecialchars($app['university_name']) ?><br>
                    <strong>Program:</strong> <?= htmlspecialchars($app['program_name']) ?><br>
                    <strong>Status:</strong> <?= htmlspecialchars($app['status'] ?? '') ?><br>
                    <strong>Applied At:</strong> <?= htmlspecialchars($app['applied_at'] ?? '') ?><br>
                    <strong>Decision At:</strong> <?= htmlspecialchars($app['decision_at'] ?? '') ?><br>
                    <strong>Notes:</strong> <?= nl2br(htmlspecialchars($app['notes'] ?? '')) ?><br>
                    <strong>Created At:</strong> <?= htmlspecialchars($app['created_at'] ?? '') ?><br>
                    <strong>Updated At:</strong> <?= htmlspecialchars($app['updated_at'] ?? '') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 