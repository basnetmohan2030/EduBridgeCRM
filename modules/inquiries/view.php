<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: list.php'); exit; }

// Get counsellors for display
$counsellors = [];
$res = $mysqli->query("SELECT id, first_name, last_name FROM counsellor ORDER BY first_name");
while ($row = $res->fetch_assoc()) $counsellors[$row['id']] = trim($row['first_name'] . ' ' . $row['last_name']);

$status_options = ['new'=>'New','contacted'=>'Contacted','in_progress'=>'In Progress','converted'=>'Converted','closed'=>'Closed','lost'=>'Lost'];
$source_options = ['Website','Walk-in','Facebook','Referral','Phone','Other'];

// Fetch inquiry
$stmt = $mysqli->prepare('SELECT * FROM inquiries WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$inq = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$inq) { header('Location: list.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inquiry - EduBridge CRM</title>
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
                <h2 class="mb-0">Inquiry Details</h2>
                <div>
                    <?php if ($inq['status'] !== 'converted' && !empty($inq['email'])): ?>
                        <a href="convert.php?id=<?= $inq['id'] ?>" class="btn btn-success"><i class="bi bi-person-plus"></i> Convert to Client</a>
                    <?php endif; ?>
                    <a href="edit.php?id=<?= $inq['id'] ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="delete.php?id=<?= $inq['id'] ?>" class="btn btn-danger ms-2" onclick="return confirm('Delete this inquiry?');"><i class="bi bi-trash"></i> Delete</a>
                    <a href="list.php" class="btn btn-secondary ms-2"><i class="bi bi-arrow-left"></i> Back to List</a>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Name</h6>
                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($inq['name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Email</h6>
                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($inq['email']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Phone</h6>
                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($inq['phone']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Source</h6>
                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($inq['source']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Status</h6>
                            <p class="mb-2 fw-semibold"><span class="badge bg-<?= $inq['status']==='new'?'primary':($inq['status']==='converted'?'success':($inq['status']==='lost'?'danger':'secondary')) ?>"><?= $status_options[$inq['status']] ?? $inq['status'] ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Assigned To</h6>
                            <p class="mb-2 fw-semibold"><?= $inq['assigned_to'] && isset($counsellors[$inq['assigned_to']]) ? htmlspecialchars($counsellors[$inq['assigned_to']]) : '<span class="text-muted">Unassigned</span>' ?></p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted mb-1">Message/Notes</h6>
                            <p class="mb-2 fw-semibold"><?= nl2br(htmlspecialchars($inq['message'])) ?></p>
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted mb-1">Created At</h6>
                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($inq['created_at']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 