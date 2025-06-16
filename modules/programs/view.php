<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
require_once '../../includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: list.php');
    exit;
}
// Fetch program with university name
$stmt = $mysqli->prepare('SELECT p.*, u.name AS university_name FROM programs p JOIN universities u ON p.university_id = u.id WHERE p.id = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$prog = $result->fetch_assoc();
$stmt->close();
if (!$prog) {
    header('Location: list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Program - EduBridge CRM</title>
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
                <h2 class="mb-0">Program Details</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h4><?= htmlspecialchars($prog['name']) ?></h4>
                    <p><strong>University:</strong> <?= htmlspecialchars($prog['university_name']) ?><br>
                    <strong>Level:</strong> <?= htmlspecialchars($prog['level']) ?><br>
                    <strong>Duration:</strong> <?= htmlspecialchars($prog['duration']) ?><br>
                    <strong>Tuition Fee:</strong> <?= $prog['tuition_fee'] !== null ? number_format($prog['tuition_fee'], 2) : '' ?><br>
                    <strong>Description:</strong> <?= nl2br(htmlspecialchars($prog['description'])) ?><br>
                    <strong>Created At:</strong> <?= htmlspecialchars($prog['created_at']) ?><br>
                    <strong>Updated At:</strong> <?= htmlspecialchars($prog['updated_at']) ?></p>
                    <?php if (in_array($user_role, ['admin', 'counsellor'])): ?>
                        <a href="edit.php?id=<?= urlencode($prog['id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete.php?id=<?= urlencode($prog['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this program?')">Delete</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 