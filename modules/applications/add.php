<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) {
    header('Location: list.php');
    exit;
}
require_once '../../includes/db.php';

$error = '';
$success = '';

// Fetch dropdown data
$clients = $mysqli->query('SELECT accountId, first_name, last_name FROM clients ORDER BY first_name, last_name')->fetch_all(MYSQLI_ASSOC);
$counsellors = $mysqli->query('SELECT accountId, first_name, last_name FROM counsellor ORDER BY first_name, last_name')->fetch_all(MYSQLI_ASSOC);
$universities = $mysqli->query('SELECT id, name FROM universities ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$programs = $mysqli->query('SELECT id, name, university_id FROM programs ORDER BY name')->fetch_all(MYSQLI_ASSOC);

function generateApplicationNumber($mysqli) {
    $year = date('Y');
    $prefix = 'APP' . $year;
    $stmt = $mysqli->prepare("SELECT application_number FROM applications WHERE application_number LIKE CONCAT(?, '%') ORDER BY application_number DESC LIMIT 1");
    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $stmt->bind_result($last);
    $stmt->fetch();
    $stmt->close();
    if ($last && preg_match('/APP' . $year . '(\d{4})/', $last, $m)) {
        $num = intval($m[1]) + 1;
    } else {
        $num = 1;
    }
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? '';
    $counsellor_id = $_POST['counsellor_id'] ?? '';
    $university_id = $_POST['university_id'] ?? '';
    $program_id = $_POST['program_id'] ?? '';
    $status = $_POST['status'] ?? 'Draft';
    $applied_at = $_POST['applied_at'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    $application_number = generateApplicationNumber($mysqli);
    $id = '';
    $uuid_stmt = $mysqli->query("SELECT UUID() AS uuid");
    if ($uuid_row = $uuid_stmt->fetch_assoc()) {
        $id = $uuid_row['uuid'];
    }
    if ($client_id && $university_id && $program_id) {
        $stmt = $mysqli->prepare('INSERT INTO applications (id, application_number, client_id, counsellor_id, university_id, program_id, status, applied_at, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->bind_param('sssssssss', $id, $application_number, $client_id, $counsellor_id, $university_id, $program_id, $status, $applied_at, $notes);
        if ($stmt->execute()) {
            $success = 'Application added successfully!';
        } else {
            $error = 'Failed to add application.';
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
    <title>Add Application - EduBridge CRM</title>
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
    <script>
    // Filter programs by university
    document.addEventListener('DOMContentLoaded', function() {
        var allPrograms = <?php echo json_encode($programs); ?>;
        var uniSelect = document.getElementById('university_id');
        var progSelect = document.getElementById('program_id');
        function filterPrograms() {
            var uniId = uniSelect.value;
            progSelect.innerHTML = '<option value="">Select Program</option>';
            allPrograms.forEach(function(p) {
                if (p.university_id === uniId) {
                    var opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.name;
                    progSelect.appendChild(opt);
                }
            });
        }
        if (uniSelect && progSelect) {
            uniSelect.addEventListener('change', filterPrograms);
            filterPrograms();
        }
    });
    </script>
</head>
<body>
<?php include '../../includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Add Application</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?= $success ?><br>
                            <a href="list.php" class="btn btn-success mt-2">Back to List</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!$success): ?>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Client *</label>
                                <select name="client_id" class="form-select" required>
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $c): ?>
                                        <option value="<?= htmlspecialchars($c['accountId']) ?>"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Counsellor</label>
                                <select name="counsellor_id" class="form-select">
                                    <option value="">Select Counsellor</option>
                                    <?php foreach ($counsellors as $co): ?>
                                        <option value="<?= htmlspecialchars($co['accountId']) ?>"><?= htmlspecialchars($co['first_name'] . ' ' . $co['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">University *</label>
                                <select name="university_id" class="form-select" id="university_id" required>
                                    <option value="">Select University</option>
                                    <?php foreach ($universities as $u): ?>
                                        <option value="<?= htmlspecialchars($u['id']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Program *</label>
                                <select name="program_id" class="form-select" id="program_id" required>
                                    <option value="">Select Program</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft">Draft</option>
                                    <option value="Submitted">Submitted</option>
                                    <option value="In Review">In Review</option>
                                    <option value="Accepted">Accepted</option>
                                    <option value="Rejected">Rejected</option>
                                    <option value="Withdrawn">Withdrawn</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Applied At</label>
                                <input type="date" name="applied_at" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Add Application</button>
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