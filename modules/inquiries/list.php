<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: ../clients/list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';

// Filters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$assigned_to = $_GET['assigned_to'] ?? '';
$where = [];
$params = [];
$types = '';
if ($search) {
    $where[] = "(name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%') OR phone LIKE CONCAT('%', ?, '%'))";
    $params[] = $search; $params[] = $search; $params[] = $search;
    $types .= 'sss';
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}
if ($assigned_to) {
    $where[] = "assigned_to = ?";
    $params[] = $assigned_to;
    $types .= 'i';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get counsellors for filter/assignment
$counsellors = [];
$res = $mysqli->query("SELECT id, first_name, last_name FROM counsellor ORDER BY first_name");
while ($row = $res->fetch_assoc()) $counsellors[$row['id']] = trim($row['first_name'] . ' ' . $row['last_name']);

// Query
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM inquiries $where_sql ORDER BY created_at DESC LIMIT ?, ?";
$stmt = $mysqli->prepare($sql);
if ($params) {
    $params[] = $offset; $params[] = $per_page;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $offset, $per_page);
}
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$total = $mysqli->query('SELECT FOUND_ROWS()')->fetch_row()[0];
$total_pages = ceil($total / $per_page);

$status_options = ['new'=>'New','contacted'=>'Contacted','in_progress'=>'In Progress','converted'=>'Converted','closed'=>'Closed','lost'=>'Lost'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries - EduBridge CRM</title>
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
                <h2 class="mb-0">Inquiries & Leads</h2>
                <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Inquiry</a>
            </div>
            <form class="mb-3" method="get">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <?php foreach ($status_options as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="assigned_to" class="form-select">
                            <option value="">All Counsellors</option>
                            <?php foreach ($counsellors as $cid=>$cname): ?>
                                <option value="<?= $cid ?>" <?= $assigned_to==$cid?'selected':'' ?>><?= htmlspecialchars($cname) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-search"></i> Filter</button>
                    </div>
                </div>
            </form>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Source</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inquiries)): ?>
                                    <tr><td colspan="8" class="text-center text-muted">No inquiries found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($inquiries as $inq): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($inq['name']) ?></td>
                                        <td><?= htmlspecialchars($inq['email']) ?></td>
                                        <td><?= htmlspecialchars($inq['phone']) ?></td>
                                        <td><span class="badge bg-<?= $inq['status']==='new'?'primary':($inq['status']==='converted'?'success':($inq['status']==='lost'?'danger':'secondary')) ?>"><?= $status_options[$inq['status']] ?? $inq['status'] ?></span></td>
                                        <td><?= $inq['assigned_to'] && isset($counsellors[$inq['assigned_to']]) ? htmlspecialchars($counsellors[$inq['assigned_to']]) : '<span class="text-muted">Unassigned</span>' ?></td>
                                        <td><?= htmlspecialchars($inq['source']) ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($inq['created_at']))) ?></td>
                                        <td>
                                            <a href="view.php?id=<?= $inq['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                            <a href="edit.php?id=<?= $inq['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                            <a href="delete.php?id=<?= $inq['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this inquiry?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php if ($total_pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&assigned_to=<?= urlencode($assigned_to) ?>"><?= $i ?></a>
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