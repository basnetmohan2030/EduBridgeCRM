<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
require_once '../../includes/db.php';

// Initialize variables
$params = [];
$types = '';
$base_where = '';

// Add role-based query modification
if ($user_role === 'counsellor') {
    $counsellor_id = $_SESSION['user_id'];
    $base_where = "WHERE a.counsellor_id = ?";
    $params[] = $counsellor_id;
    $types = 's';
}

// Search/filter
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$counsellor_filter = $_GET['counsellor_id'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = $base_where;

if ($search !== '') {
    $where .= ($where ? " AND " : "WHERE ") . 
              "(CONCAT(c.first_name, ' ', c.middle_name, ' ', c.last_name) LIKE ? OR u.name LIKE ? OR p.name LIKE ?)";
    $search_param = '%' . $search . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}
if ($status_filter) {
    $where .= ($where ? " AND " : "WHERE ") . "a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($counsellor_filter && $user_role !== 'counsellor') {
    $where .= ($where ? " AND " : "WHERE ") . "a.counsellor_id = ?";
    $params[] = $counsellor_filter;
    $types .= 's';
}
// Count total
$count_sql = "SELECT COUNT(*) FROM applications a 
              INNER JOIN clients c ON a.client_id = c.accountId
              INNER JOIN universities u ON a.university_id = u.id
              INNER JOIN programs p ON a.program_id = p.id " .
              $where;
$count_stmt = $mysqli->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total);
$count_stmt->fetch();
$count_stmt->close();
// Fetch applications
$sql = "SELECT a.*, 
        CONCAT(c.first_name, ' ', c.middle_name, ' ', c.last_name) as client_name,
        u.name as university_name,
        p.name as program_name
        FROM applications a
        INNER JOIN clients c ON a.client_id = c.accountId
        INNER JOIN universities u ON a.university_id = u.id
        INNER JOIN programs p ON a.program_id = p.id " .
        $where . " 
        ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
if ($params) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$applications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$total_pages = ceil($total / $limit);
// Fetch counsellors for filter
$counsellors = $mysqli->query('SELECT accountId, first_name, last_name FROM counsellor ORDER BY first_name, last_name')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Applications - EduBridge CRM</title>
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
                <h2 class="mb-0">Applications</h2>
                <?php if (in_array($user_role, ['admin', 'counsellor'])): ?>
                    <a href="add.php" class="btn btn-primary"><i class="bi bi-plus"></i> Add Application</a>
                <?php endif; ?>
            </div>
            <form class="mb-3" method="get">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by application number, client, university, program" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <?php foreach (["Draft","Submitted","In Review","Accepted","Rejected","Withdrawn"] as $status): ?>
                                <option value="<?= $status ?>" <?= $status_filter === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="counsellor_id" class="form-select">
                            <option value="">All Counsellors</option>
                            <?php foreach ($counsellors as $co): ?>
                                <option value="<?= htmlspecialchars($co['accountId']) ?>" <?= $counsellor_filter === $co['accountId'] ? 'selected' : '' ?>><?= htmlspecialchars($co['first_name'] . ' ' . $co['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
                    </div>
                </div>
            </form>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>App #</th>
                                    <th>Client</th>
                                    <th>University</th>
                                    <th>Program</th>
                                    <th>Status</th>
                                    <th>Applied At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($app['application_number']) ?></td>
                                        <td><?= htmlspecialchars($app['client_name']) ?></td>
                                        <td><?= htmlspecialchars($app['university_name']) ?></td>
                                        <td><?= htmlspecialchars($app['program_name']) ?></td>
                                        <td><?= htmlspecialchars($app['status']) ?></td>
                                        <td><?= $app['applied_at'] ? htmlspecialchars($app['applied_at']) : '' ?></td>
                                        <td>
                                            <a href="view.php?id=<?= urlencode($app['id']) ?>" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                                            <?php if ($user_role === 'admin' || ($user_role === 'counsellor' && $app['counsellor_id'] === $_SESSION['user_id'])): ?>
                                                <a href="edit.php?id=<?= urlencode($app['id']) ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <?php endif; ?>
                                            <?php if ($user_role === 'admin'): ?>
                                                <a href="delete.php?id=<?= urlencode($app['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this application?');" title="Delete"><i class="bi bi-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($applications)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No applications found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&counsellor_id=<?= urlencode($counsellor_filter) ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html> 