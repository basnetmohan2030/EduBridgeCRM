<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
$user_id = current_user_id();

require_once '../../includes/db.php';

$id = intval($_GET['id'] ?? 0);
$client_id = $_GET['client_id'] ?? null;

if (!$id || !$client_id) {
    header('Location: ' . ($user_role === 'client' ? 'view.php?id=' . $user_id : 'list.php'));
    exit;
}

// Check permissions
$can_edit = false;
if ($user_role === 'admin') {
    $can_edit = true;
} elseif ($user_role === 'counsellor') {
    // Check if client is assigned to this counsellor
    $check_sql = "SELECT c.id FROM clients c 
                  INNER JOIN counsellor co ON c.handled_by = co.id 
                  WHERE c.id = ? AND co.accountId = ?";
    $stmt = $mysqli->prepare($check_sql);
    $stmt->bind_param('is', $client_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $can_edit = $result->num_rows > 0;
    $stmt->close();
} elseif ($user_role === 'client') {
    // Clients can edit their own academics
    $check_sql = "SELECT id FROM clients WHERE id = ? AND accountId = ?";
    $stmt = $mysqli->prepare($check_sql);
    $stmt->bind_param('is', $client_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $can_edit = $result->num_rows > 0;
    $stmt->close();
}

if (!$can_edit) {
    header('Location: ' . ($user_role === 'client' ? 'view.php?id=' . $user_id : 'list.php'));
    exit('Unauthorized access');
}

$stmt = $mysqli->prepare('SELECT * FROM student_academics WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$academic = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$academic) { header('Location: view.php?id=' . $client_id . '&tab=academics'); exit; }
$error = '';
$degree_options = ['SEE', '+2', 'Bachelor', 'Master', 'PhD'];
$grading_options = ['Percentage', 'GPA', 'CGPA'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $degree = trim($_POST['degree'] ?? '');
    $board = trim($_POST['board'] ?? '');
    $year_of_passing = intval($_POST['year_of_passing'] ?? 0);
    $grade = trim($_POST['grade'] ?? '');
    $grading_system = trim($_POST['grading_system'] ?? '');
    $school_name = trim($_POST['school_name'] ?? '');
    $school_address = trim($_POST['school_address'] ?? '');
    $school_city = trim($_POST['school_city'] ?? '');
    $school_state = trim($_POST['school_state'] ?? '');
    $school_country = trim($_POST['school_country'] ?? '');
    $school_zip_code = trim($_POST['school_zip_code'] ?? '');
    if ($degree && $board && $year_of_passing && $grade && $grading_system && $school_name) {
        $stmt = $mysqli->prepare('UPDATE student_academics SET degree=?, board=?, year_of_passing=?, grade=?, grading_system=?, school_name=?, school_address=?, school_city=?, school_state=?, school_country=?, school_zip_code=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('ssissssssssi', $degree, $board, $year_of_passing, $grade, $grading_system, $school_name, $school_address, $school_city, $school_state, $school_country, $school_zip_code, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: view.php?id=' . $client_id . '&tab=academics'); exit;
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
    <title>Edit Academic - EduBridge CRM</title>
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
                <h2 class="mb-0">Edit Academic Record</h2>
                <a href="view.php?id=<?= $client_id ?>&tab=academics" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Degree *</label>
                                <select name="degree" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($degree_options as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $academic['degree'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Board *</label>
                                <input type="text" name="board" class="form-control" value="<?= htmlspecialchars($academic['board']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Year of Passing *</label>
                                <input type="number" name="year_of_passing" class="form-control" min="1900" max="2100" value="<?= htmlspecialchars($academic['year_of_passing']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Grade *</label>
                                <input type="text" name="grade" class="form-control" value="<?= htmlspecialchars($academic['grade']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Grading System *</label>
                                <select name="grading_system" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($grading_options as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $academic['grading_system'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">School Name *</label>
                                <input type="text" name="school_name" class="form-control" value="<?= htmlspecialchars($academic['school_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">School Address</label>
                                <input type="text" name="school_address" class="form-control" value="<?= htmlspecialchars($academic['school_address']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">School City</label>
                                <input type="text" name="school_city" class="form-control" value="<?= htmlspecialchars($academic['school_city']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">School State</label>
                                <input type="text" name="school_state" class="form-control" value="<?= htmlspecialchars($academic['school_state']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">School Country</label>
                                <input type="text" name="school_country" class="form-control" value="<?= htmlspecialchars($academic['school_country']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">School Zip Code</label>
                                <input type="text" name="school_zip_code" class="form-control" value="<?= htmlspecialchars($academic['school_zip_code']) ?>">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Academic</button>
                            <a href="view.php?id=<?= $client_id ?>&tab=academics" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 