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
// Fetch university
$stmt = $mysqli->prepare('SELECT * FROM universities WHERE id = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$uni = $result->fetch_assoc();
$stmt->close();
if (!$uni) {
    header('Location: list.php');
    exit;
}
// Fetch programs
$stmt = $mysqli->prepare('SELECT * FROM programs WHERE university_id = ? ORDER BY name');
$stmt->bind_param('s', $id);
$stmt->execute();
$programs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View University - EduBridge CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">University Details</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="uniTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="programs-tab" data-bs-toggle="tab" data-bs-target="#programs" type="button" role="tab">Programs</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3" id="uniTabContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <?php if ($uni['logo']): ?>
                                        <img src="<?= BASE_URL . '/' . htmlspecialchars($uni['logo']) ?>" alt="Logo" style="max-width:120px;max-height:120px;">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-9">
                                    <h4><?= htmlspecialchars($uni['name']) ?></h4>
                                    <p><strong>Country:</strong> <?= htmlspecialchars($uni['country']) ?><br>
                                    <strong>City:</strong> <?= htmlspecialchars($uni['city']) ?><br>
                                    <strong>Website:</strong> <?php if ($uni['website']): ?><a href="<?= htmlspecialchars($uni['website']) ?>" target="_blank"><?= htmlspecialchars($uni['website']) ?></a><?php endif; ?><br>
                                    <strong>Description:</strong> <?= nl2br(htmlspecialchars($uni['description'])) ?><br>
                                    <strong>Created At:</strong> <?= htmlspecialchars($uni['created_at']) ?><br>
                                    <strong>Updated At:</strong> <?= htmlspecialchars($uni['updated_at']) ?></p>
                                    <?php if (in_array($user_role, ['admin', 'counsellor'])): ?>
                                        <a href="edit.php?id=<?= urlencode($uni['id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?= urlencode($uni['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this university?')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="programs" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Programs</h5>
                                <?php if (in_array($user_role, ['admin', 'counsellor'])): ?>
                                    <a href="../programs/add.php?university_id=<?= urlencode($uni['id']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Program</a>
                                <?php endif; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Level</th>
                                            <th>Duration</th>
                                            <th>Tuition Fee</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($programs as $prog): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($prog['name']) ?></td>
                                                <td><?= htmlspecialchars($prog['level']) ?></td>
                                                <td><?= htmlspecialchars($prog['duration']) ?></td>
                                                <td><?= $prog['tuition_fee'] !== null ? number_format($prog['tuition_fee'], 2) : '' ?></td>
                                                <td>
                                                    <a href="../programs/view.php?id=<?= urlencode($prog['id']) ?>" class="btn btn-info btn-sm">View</a>
                                                    <?php if (in_array($user_role, ['admin', 'counsellor'])): ?>
                                                        <a href="../programs/edit.php?id=<?= urlencode($prog['id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                                                        <a href="../programs/delete.php?id=<?= urlencode($prog['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this program?')">Delete</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($programs)): ?>
                                            <tr><td colspan="5" class="text-center text-muted">No programs found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 