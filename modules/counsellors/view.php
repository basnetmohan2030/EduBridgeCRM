<?php
require_once '../../includes/auth.php';
require_login();
if (current_user_role() !== 'admin') { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: list.php'); exit; }

$stmt = $mysqli->prepare('SELECT * FROM counsellor WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$counsellor = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$counsellor) { header('Location: list.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Counsellor - EduBridge CRM</title>
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
                <h2 class="mb-0">Counsellor Details</h2>
                <div>
                    <a href="edit.php?id=<?= $counsellor['id'] ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="delete.php?id=<?= $counsellor['id'] ?>" class="btn btn-danger ms-2" onclick="return confirm('Delete this counsellor?');"><i class="bi bi-trash"></i> Delete</a>
                    <a href="list.php" class="btn btn-secondary ms-2"><i class="bi bi-arrow-left"></i> Back to List</a>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-3 text-center">
                        <?php if (!empty($counsellor['profile_picture'])): ?>
                            <img src="<?= BASE_URL . htmlspecialchars($counsellor['profile_picture']) ?>" class="img-thumbnail mb-3" style="width:100%;max-width:240px;max-height:240px;object-fit:cover;" alt="Profile Picture">
                        <?php else: ?>
                            <div class="bg-secondary mb-3 d-flex align-items-center justify-content-center" style="width:100%;max-width:240px;height:180px;">
                                <i class="bi bi-person" style="font-size:4rem;color:#fff;"></i>
                            </div>
                        <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Name</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars(trim($counsellor['first_name'] . ' ' . $counsellor['middle_name'] . ' ' . $counsellor['last_name'])) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Email</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['email']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Phone</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['phone']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Gender</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['gender']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Date of Birth</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['dob']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Nationality</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['nationality']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Address</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['address']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">City</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['city']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">State</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['state']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Country</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['country']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted mb-1">Zip Code</h6>
                                            <p class="mb-2 fw-semibold"><?= htmlspecialchars($counsellor['zip_code']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 