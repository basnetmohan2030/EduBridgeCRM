<?php
require_once '../../includes/auth.php';
require_login();
$user_role = htmlspecialchars(current_user_role());
require_once '../../includes/db.php';

$application_id = $_GET['id'] ?? '';
if (empty($application_id)) {
    header('Location: list.php');
    exit('Invalid application ID');
}

// Check permissions
$can_edit = false;
if ($user_role === 'admin') {
    $can_edit = true;
} elseif ($user_role === 'counsellor') {
    // Check if application belongs to this counsellor
    $check_sql = "SELECT a.id FROM applications a 
                  WHERE a.id = ? AND a.counsellor_id = ?";
    $stmt = $mysqli->prepare($check_sql);
    $stmt->bind_param('ss', $application_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $can_edit = $result->num_rows > 0;
    $stmt->close();
}

if (!$can_edit) {
    header('Location: list.php');
    exit('Unauthorized access');
}

// Fetch application
$stmt = $mysqli->prepare('SELECT * FROM applications WHERE id = ? LIMIT 1');
$stmt->bind_param('s', $application_id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();
$stmt->close();

if (!$app) {
    header('Location: list.php');
    exit('Application not found');
}

// Fetch dropdown data
$clients = $mysqli->query('SELECT accountId, first_name, last_name FROM clients ORDER BY first_name, last_name')->fetch_all(MYSQLI_ASSOC);
$counsellors = $mysqli->query('SELECT accountId, first_name, last_name FROM counsellor ORDER BY first_name, last_name')->fetch_all(MYSQLI_ASSOC);
$universities = $mysqli->query('SELECT id, name FROM universities ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$programs = $mysqli->query('SELECT id, name, university_id FROM programs ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? '';
    $counsellor_id = $_POST['counsellor_id'] ?? null; // Allow NULL for counsellor_id
    $university_id = $_POST['university_id'] ?? '';
    $program_id = $_POST['program_id'] ?? '';
    $status = $_POST['status'] ?? 'Draft';
    $applied_at = $_POST['applied_at'] ?? null;
    $notes = trim($_POST['notes'] ?? '');

    if ($client_id && $university_id && $program_id) {
        if (empty($counsellor_id)) {
            // If counsellor_id is empty, set it to NULL
            $stmt = $mysqli->prepare('UPDATE applications SET client_id=?, counsellor_id=NULL, university_id=?, program_id=?, status=?, applied_at=?, notes=?, updated_at=NOW() WHERE id=?');
            $stmt->bind_param('sssssss', $client_id, $university_id, $program_id, $status, $applied_at, $notes, $application_id);
        } else {
            $stmt = $mysqli->prepare('UPDATE applications SET client_id=?, counsellor_id=?, university_id=?, program_id=?, status=?, applied_at=?, notes=?, updated_at=NOW() WHERE id=?');
            $stmt->bind_param('ssssssss', $client_id, $counsellor_id, $university_id, $program_id, $status, $applied_at, $notes, $application_id);
        }
        
        if ($stmt->execute()) {
            $success = 'Application updated successfully!';
            $app = array_merge($app, [
                'client_id' => $client_id,
                'counsellor_id' => $counsellor_id,
                'university_id' => $university_id,
                'program_id' => $program_id,
                'status' => $status,
                'applied_at' => $applied_at,
                'notes' => $notes
            ]);
        } else {
            $error = 'Failed to update application: ' . $mysqli->error;
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
    <title>Edit Application - EduBridge CRM</title>
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
                    if (p.id === "<?= $app['program_id'] ?>") opt.selected = true;
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
                <h2 class="mb-0">Edit Application</h2>
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
                                <label class="form-label">Application #</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($app['application_number']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client *</label>
                                <select name="client_id" class="form-select" required>
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $c): ?>
                                        <option value="<?= htmlspecialchars($c['accountId']) ?>" <?= $app['client_id'] == $c['accountId'] ? 'selected' : '' ?>><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Counsellor</label>
                                <select name="counsellor_id" class="form-select">
                                    <option value="">Select Counsellor</option>
                                    <?php foreach ($counsellors as $co): ?>
                                        <option value="<?= htmlspecialchars($co['accountId']) ?>" <?= $app['counsellor_id'] == $co['accountId'] ? 'selected' : '' ?>><?= htmlspecialchars($co['first_name'] . ' ' . $co['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">University *</label>
                                <select name="university_id" class="form-select" id="university_id" required>
                                    <option value="">Select University</option>
                                    <?php foreach ($universities as $u): ?>
                                        <option value="<?= htmlspecialchars($u['id']) ?>" <?= $app['university_id'] == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
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
                                    <option value="Draft" <?= $app['status'] == 'Draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="Submitted" <?= $app['status'] == 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                                    <option value="In Review" <?= $app['status'] == 'In Review' ? 'selected' : '' ?>>In Review</option>
                                    <option value="Accepted" <?= $app['status'] == 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                                    <option value="Rejected" <?= $app['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option value="Withdrawn" <?= $app['status'] == 'Withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Applied At</label>
                                <input type="date" name="applied_at" class="form-control" value="<?= htmlspecialchars($app['applied_at']) ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($app['notes']) ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Application</button>
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