<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
$user_id = current_user_id();

require_once '../../includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: list.php');
    exit;
}

// Check permissions
$can_access = false;
$can_edit = false;
$can_delete = false;

if ($user_role === 'admin') {
    $can_access = true;
    $can_edit = true;
    $can_delete = true;
} elseif ($user_role === 'counsellor') {
    // Check if client is assigned to this counsellor
    $check_sql = "SELECT c.id FROM clients c 
                  INNER JOIN counsellor co ON c.handled_by = co.id 
                  WHERE c.id = ? AND co.accountId = ?";
    $stmt = $mysqli->prepare($check_sql);
    $stmt->bind_param('is', $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $can_access = $result->num_rows > 0;
    $can_edit = $can_access;
    $stmt->close();
} elseif ($user_role === 'client') {
    // Clients can only view and edit their own profile
    $check_sql = "SELECT id FROM clients WHERE accountId = ?";
    $stmt = $mysqli->prepare($check_sql);
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($client = $result->fetch_assoc()) {
        // Convert both IDs to strings for comparison
        $can_access = (string)$client['id'] === (string)$id;
        $can_edit = $can_access;
    }
    $stmt->close();
}

if (!$can_access) {
    header('Location: ' . ($user_role === 'client' ? '../../dashboard.php' : 'list.php'));
    exit('Unauthorized access');
}

// Fetch client profile
$stmt = $mysqli->prepare('SELECT * FROM clients WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$client) {
    header('Location: ' . ($user_role === 'client' ? '../../dashboard.php' : 'list.php'));
    exit;
}

// Fetch related data
// Academics
$academics = [];
$stmt = $mysqli->prepare('SELECT * FROM student_academics WHERE client_id = ? ORDER BY year_of_passing DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $academics[] = $row;
$stmt->close();
// Documents
$documents = [];
$stmt = $mysqli->prepare('SELECT * FROM student_documents WHERE client_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $documents[] = $row;
$stmt->close();
// Preferences
$preferences = [];
$stmt = $mysqli->prepare('SELECT * FROM student_preferences WHERE client_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $preferences[] = $row;
$stmt->close();
// Test Scores
$test_scores = [];
$stmt = $mysqli->prepare('SELECT * FROM student_test_scores WHERE client_id = ? ORDER BY date_of_exam DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $test_scores[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $user_role === 'client' ? 'My Profile' : 'View Client' ?> - EduBridge CRM</title>
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
                <h2 class="mb-0"><?= $user_role === 'client' ? 'My Profile' : 'Client Details' ?></h2>
                <div class="btn-group">
                    <?php if ($can_edit): ?>
                    <a href="edit.php?id=<?= $id ?>" class="btn btn-warning me-2">
                        <i class="bi bi-pencil"></i> <?= $user_role === 'client' ? 'Edit Profile' : 'Edit Client' ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($can_delete): ?>
                    <a href="delete.php?id=<?= $id ?>" class="btn btn-danger me-2" onclick="return confirm('Are you sure you want to delete this client? This action cannot be undone.');">
                        <i class="bi bi-trash"></i> Delete Client
                    </a>
                    <?php endif; ?>
                    <?php if ($user_role !== 'client'): ?>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="clientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="academics-tab" data-bs-toggle="tab" data-bs-target="#academics" type="button" role="tab">Academics</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">Documents</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences" type="button" role="tab">Preferences</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="scores-tab" data-bs-toggle="tab" data-bs-target="#scores" type="button" role="tab">Test Scores</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="clientTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="row g-4 align-items-center">
                                <div class="col-md-3 text-center">
                                <?php if (!empty($client['profile_picture'])): ?>
                                    <img src="<?= BASE_URL . htmlspecialchars($client['profile_picture']) ?>" class="img-thumbnail mb-3" style="width:100%;max-width:240px;max-height:240px;object-fit:cover;" alt="Profile Picture">
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
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars(trim($client['first_name'] . ' ' . $client['middle_name'] . ' ' . $client['last_name'])) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-1">Email</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['email']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-1">Phone</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['phone']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-1">Gender</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['gender']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-1">Marital Status</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['marital_status']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-muted mb-1">Address</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['address']) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="text-muted mb-1">City</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['city']) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="text-muted mb-1">State</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['state']) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="text-muted mb-1">Country</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['country']) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="text-muted mb-1">Zip Code</h6>
                                                    <p class="mb-2 fw-semibold"><?= htmlspecialchars($client['zip_code']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Academics Tab -->
                        <div class="tab-pane fade" id="academics" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5>Academic Records</h5>
                                <?php if ($can_edit || $user_role === 'client'): ?>
                                <a href="academics_add.php?client_id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Academic</a>
                                <?php endif; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Degree</th><th>Board</th><th>Year</th><th>Grade</th><th>School</th>
                                            <?php if ($can_edit): ?><th>Actions</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($academics)): ?>
                                            <tr><td colspan="<?= $can_edit ? '6' : '5' ?>" class="text-center text-muted">No records.</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($academics as $a): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($a['degree']) ?></td>
                                                <td><?= htmlspecialchars($a['board']) ?></td>
                                                <td><?= htmlspecialchars($a['year_of_passing']) ?></td>
                                                <td><?= htmlspecialchars($a['grade']) ?></td>
                                                <td><?= htmlspecialchars($a['school_name']) ?></td>
                                                <?php if ($can_edit || ($user_role === 'client' && $id === $user_id)): ?>
                                                <td>
                                                    <a href="academics_edit.php?id=<?= $a['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                    <a href="academics_delete.php?id=<?= $a['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5>Documents</h5>
                                <?php if ($can_edit || $user_role === 'client'): ?>
                                <a href="documents_add.php?client_id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Document</a>
                                <?php endif; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th><th>Name</th><th>File</th>
                                            <?php if ($can_edit): ?><th>Actions</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($documents)): ?>
                                            <tr><td colspan="<?= $can_edit ? '4' : '3' ?>" class="text-center text-muted">No documents.</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($documents as $d): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($d['document_type']) ?></td>
                                                <td><?= htmlspecialchars($d['document_name']) ?></td>
                                                <td><a href="<?= BASE_URL . htmlspecialchars($d['document_url']) ?>" target="_blank">View</a></td>
                                                <?php if ($can_edit || ($user_role === 'client' && $id === $user_id)): ?>
                                                <td>
                                                    <a href="documents_edit.php?id=<?= $d['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                    <a href="documents_delete.php?id=<?= $d['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this document?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Preferences Tab -->
                        <div class="tab-pane fade" id="preferences" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5>Preferences</h5>
                                <?php if ($can_edit || $user_role === 'client'): ?>
                                <a href="preferences_add.php?client_id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Preference</a>
                                <?php endif; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Visa Type</th><th>Country</th><th>Course</th><th>Level</th><th>Intake</th>
                                            <?php if ($can_edit): ?><th>Actions</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($preferences)): ?>
                                            <tr><td colspan="<?= $can_edit ? '6' : '5' ?>" class="text-center text-muted">No preferences.</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($preferences as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['visa_type']) ?></td>
                                                <td><?= htmlspecialchars($p['country']) ?></td>
                                                <td><?= htmlspecialchars($p['course']) ?></td>
                                                <td><?= htmlspecialchars($p['level']) ?></td>
                                                <td><?= htmlspecialchars($p['intake']) ?></td>
                                                <?php if ($can_edit || ($user_role === 'client' && $id === $user_id)): ?>
                                                <td>
                                                    <a href="preferences_edit.php?id=<?= $p['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                    <a href="preferences_delete.php?id=<?= $p['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this preference?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Test Scores Tab -->
                        <div class="tab-pane fade" id="scores" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5>Test Scores</h5>
                                <?php if ($can_edit || $user_role === 'client'): ?>
                                <a href="scores_add.php?client_id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Test Score</a>
                                <?php endif; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Exam</th><th>Date</th><th>Overall</th><th>Listening</th><th>Reading</th><th>Writing</th><th>Speaking</th>
                                            <?php if ($can_edit): ?><th>Actions</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($test_scores)): ?>
                                            <tr><td colspan="<?= $can_edit ? '8' : '7' ?>" class="text-center text-muted">No test scores.</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($test_scores as $s): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($s['exam_type']) ?></td>
                                                <td><?= htmlspecialchars($s['date_of_exam']) ?></td>
                                                <td><?= htmlspecialchars($s['overall_score']) ?></td>
                                                <td><?= htmlspecialchars($s['listening']) ?></td>
                                                <td><?= htmlspecialchars($s['reading']) ?></td>
                                                <td><?= htmlspecialchars($s['writing']) ?></td>
                                                <td><?= htmlspecialchars($s['speaking']) ?></td>
                                                <?php if ($can_edit || ($user_role === 'client' && $id === $user_id)): ?>
                                                <td>
                                                    <a href="scores_edit.php?id=<?= $s['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                    <a href="scores_delete.php?id=<?= $s['id'] ?>&client_id=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this score?');"><i class="bi bi-trash"></i></a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
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
<script>
// Persist active tab using URL hash
const hash = window.location.hash;
if (hash) {
    const tabTrigger = document.querySelector(`button[data-bs-target='${hash}']`);
    if (tabTrigger) {
        const tab = new bootstrap.Tab(tabTrigger);
        tab.show();
    }
}
// Update URL hash when tab is changed
const tabButtons = document.querySelectorAll('#clientTabs button[data-bs-toggle="tab"]');
tabButtons.forEach(btn => {
    btn.addEventListener('shown.bs.tab', function (e) {
        history.replaceState(null, null, e.target.dataset.bsTarget);
    });
});
</script>
</body>
</html> 