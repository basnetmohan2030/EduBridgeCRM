<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
$id = intval($_GET['id'] ?? 0);
$client_id = intval($_GET['client_id'] ?? 0);
if (!$id || !$client_id) { header('Location: list.php'); exit; }
$stmt = $mysqli->prepare('SELECT * FROM student_preferences WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$pref = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pref) { header('Location: view.php?id=' . $client_id . '&tab=preferences'); exit; }
$error = '';
$visa_types = ['Student', 'Visitor', 'Dependent', 'Work', 'Other'];
$levels = ['Diploma', 'Bachelor', 'Master', 'PhD', 'Other'];
$intakes = ['Spring', 'Summer', 'Fall', 'Winter', 'Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visa_type = trim($_POST['visa_type'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $intake = trim($_POST['intake'] ?? '');
    if ($visa_type && $country && $course && $level && $intake) {
        $stmt = $mysqli->prepare('UPDATE student_preferences SET visa_type=?, country=?, course=?, level=?, intake=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('sssssi', $visa_type, $country, $course, $level, $intake, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: view.php?id=' . $client_id . '&tab=preferences'); exit;
    } else {
        $error = 'Please fill all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Preference - EduBridge CRM</title>
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
                <h2 class="mb-0">Edit Preference</h2>
                <a href="view.php?id=<?= $client_id ?>&tab=preferences" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Visa Type *</label>
                                <select name="visa_type" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($visa_types as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $pref['visa_type'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country *</label>
                                <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($pref['country']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Course *</label>
                                <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($pref['course']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Level *</label>
                                <select name="level" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($levels as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $pref['level'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Intake *</label>
                                <select name="intake" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($intakes as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $pref['intake'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Preference</button>
                            <a href="view.php?id=<?= $client_id ?>&tab=preferences" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 