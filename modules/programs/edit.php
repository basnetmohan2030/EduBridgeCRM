<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) {
    header('Location: list.php');
    exit;
}
require_once '../../includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: list.php');
    exit;
}
// Fetch program
$stmt = $mysqli->prepare('SELECT * FROM programs WHERE id = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$prog = $result->fetch_assoc();
$stmt->close();
if (!$prog) {
    header('Location: list.php');
    exit;
}
// Fetch universities for dropdown
$unis = $mysqli->query('SELECT id, name FROM universities ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $university_id = $_POST['university_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $level = $_POST['level'] ?? 'Bachelors';
    $duration = trim($_POST['duration'] ?? '');
    $tuition_fee = $_POST['tuition_fee'] !== '' ? floatval($_POST['tuition_fee']) : null;
    $description = trim($_POST['description'] ?? '');

    if ($university_id && $name) {
        $stmt = $mysqli->prepare('UPDATE programs SET university_id=?, name=?, level=?, duration=?, tuition_fee=?, description=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('sssssss', $university_id, $name, $level, $duration, $tuition_fee, $description, $id);
        if ($stmt->execute()) {
            $success = 'Program updated successfully!';
            // Refresh data
            $prog = array_merge($prog, [
                'university_id' => $university_id,
                'name' => $name,
                'level' => $level,
                'duration' => $duration,
                'tuition_fee' => $tuition_fee,
                'description' => $description
            ]);
        } else {
            $error = 'Failed to update program.';
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
    <title>Edit Program - EduBridge CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../../includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Edit Program</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success">
                            <?= $success ?><br>
                            <a href="list.php" class="btn btn-success mt-2">Back to List</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!$success): ?>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">University *</label>
                                <select name="university_id" class="form-select" required>
                                    <option value="">Select University</option>
                                    <?php foreach ($unis as $u): ?>
                                        <option value="<?= htmlspecialchars($u['id']) ?>" <?= ($prog['university_id'] == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($prog['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Level</label>
                                <select name="level" class="form-select">
                                    <option value="Bachelors" <?= $prog['level'] == 'Bachelors' ? 'selected' : '' ?>>Bachelors</option>
                                    <option value="Masters" <?= $prog['level'] == 'Masters' ? 'selected' : '' ?>>Masters</option>
                                    <option value="PhD" <?= $prog['level'] == 'PhD' ? 'selected' : '' ?>>PhD</option>
                                    <option value="Diploma" <?= $prog['level'] == 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                                    <option value="Certificate" <?= $prog['level'] == 'Certificate' ? 'selected' : '' ?>>Certificate</option>
                                    <option value="Other" <?= $prog['level'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duration</label>
                                <input type="text" name="duration" class="form-control" value="<?= htmlspecialchars($prog['duration']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tuition Fee</label>
                                <input type="number" step="0.01" name="tuition_fee" class="form-control" value="<?= htmlspecialchars($prog['tuition_fee']) ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($prog['description']) ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Program</button>
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