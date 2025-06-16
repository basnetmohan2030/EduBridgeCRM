<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
$user_id = current_user_id();

require_once '../../includes/db.php';

// Get the correct client ID
$client_id = null;
if ($user_role === 'client') {
    // For clients, get their actual client ID from the clients table
    $stmt = $mysqli->prepare('SELECT id FROM clients WHERE accountId = ?');
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($client = $result->fetch_assoc()) {
        $client_id = $client['id'];
    }
    $stmt->close();
} else {
    $client_id = intval($_GET['client_id'] ?? 0);
}

// Check permissions
$can_add = false;
if ($user_role === 'admin') {
    $can_add = true;
} elseif ($user_role === 'counsellor') {
    // Check if client is assigned to this counsellor
    $check_sql = "SELECT c.id FROM clients c 
                  INNER JOIN counsellor co ON c.handled_by = co.id 
                  WHERE c.id = ? AND co.accountId = ?";
    $stmt = $mysqli->prepare($check_sql);
    $stmt->bind_param('is', $client_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $can_add = $result->num_rows > 0;
    $stmt->close();
} elseif ($user_role === 'client' && $client_id) {
    // Clients can add their own preferences
    $can_add = true;
}

if (!$can_add || !$client_id) {
    header('Location: ' . ($user_role === 'client' ? 'view.php?id=' . $client_id : 'list.php'));
    exit('Unauthorized access');
}

$error = '';
$visa_types = ['Student', 'Work', 'Tourist', 'Business', 'Other'];
$countries = ['USA', 'UK', 'Canada', 'Australia', 'New Zealand', 'Germany', 'France', 'Other'];
$levels = ['Undergraduate', 'Graduate', 'PhD', 'Diploma', 'Certificate'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visa_type = trim($_POST['visa_type'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $intake = trim($_POST['intake'] ?? '');
    
    if ($visa_type && $country && $course && $level && $intake) {
        $stmt = $mysqli->prepare('INSERT INTO student_preferences (client_id, visa_type, country, course, level, intake, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->bind_param('isssss', $client_id, $visa_type, $country, $course, $level, $intake);
        $stmt->execute();
        $stmt->close();
        header('Location: view.php?id=' . $client_id . '&tab=preferences');
        exit;
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
    <title>Add Preference - EduBridge CRM</title>
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
                <h2 class="mb-0">Add Preference</h2>
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
                                    <?php foreach ($visa_types as $type): ?>
                                        <option value="<?= $type ?>"><?= $type ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country *</label>
                                <select name="country" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= $country ?>"><?= $country ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Course *</label>
                                <input type="text" name="course" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Level *</label>
                                <select name="level" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($levels as $level): ?>
                                        <option value="<?= $level ?>"><?= $level ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Intake *</label>
                                <input type="month" name="intake" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Add Preference</button>
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