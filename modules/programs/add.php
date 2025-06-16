<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) {
    header('Location: ../universities/list.php');
    exit;
}
require_once '../../includes/db.php';

$error = '';
$success = '';
$university_id = $_GET['university_id'] ?? '';

// Fetch universities for dropdown
$unis = $mysqli->query('SELECT id, name FROM universities ORDER BY name')->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $university_id = $_POST['university_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $level = $_POST['level'] ?? 'Bachelors';
    $duration = trim($_POST['duration'] ?? '');
    $tuition_fee = $_POST['tuition_fee'] !== '' ? floatval($_POST['tuition_fee']) : null;
    $description = trim($_POST['description'] ?? '');

    if ($university_id && $name) {
        // Generate UUID for id
        $id = '';
        $uuid_stmt = $mysqli->query("SELECT UUID() AS uuid");
        if ($uuid_row = $uuid_stmt->fetch_assoc()) {
            $id = $uuid_row['uuid'];
        }
        $stmt = $mysqli->prepare('INSERT INTO programs (id, university_id, name, level, duration, tuition_fee, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->bind_param('sssssss', $id, $university_id, $name, $level, $duration, $tuition_fee, $description);
        if ($stmt->execute()) {
            $success = 'Program added successfully!';
        } else {
            $error = 'Failed to add program.';
        }
        $stmt->close();
    } else {
        $error = 'Please fill all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Program - EduBridge CRM</title>
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
                <h2 class="mb-0">Add Program</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success">
                            <?= $success ?><br>
                            <?php if ($university_id): ?>
                                <a href="../universities/view.php?id=<?= urlencode($university_id) ?>" class="btn btn-success mt-2">Back to University</a>
                            <?php else: ?>
                                <a href="list.php" class="btn btn-success mt-2">Back to List</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$success): ?>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">University *</label>
                                <select name="university_id" class="form-select" required <?= $university_id ? 'readonly disabled' : '' ?>>
                                    <option value="">Select University</option>
                                    <?php foreach ($unis as $u): ?>
                                        <option value="<?= htmlspecialchars($u['id']) ?>" <?= ($university_id == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($university_id): ?><input type="hidden" name="university_id" value="<?= htmlspecialchars($university_id) ?>"><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Level</label>
                                <select name="level" class="form-select">
                                    <option value="Bachelors">Bachelors</option>
                                    <option value="Masters">Masters</option>
                                    <option value="PhD">PhD</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="Certificate">Certificate</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duration</label>
                                <input type="text" name="duration" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tuition Fee</label>
                                <input type="number" step="0.01" name="tuition_fee" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Add Program</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 