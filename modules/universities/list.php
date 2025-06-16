<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
require_once '../../includes/db.php';

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
$types = '';
if ($search) {
    $where = "WHERE name LIKE ? OR country LIKE ?";
    $params = ["%$search%", "%$search%"];
    $types = 'ss';
}
// Count total
$count_sql = "SELECT COUNT(*) FROM universities $where";
$count_stmt = $mysqli->prepare($count_sql);
if ($where) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total);
$count_stmt->fetch();
$count_stmt->close();
// Fetch universities
$sql = "SELECT * FROM universities $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $mysqli->prepare($sql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$universities = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Universities - EduBridge CRM</title>
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
                <h2 class="mb-0">Universities</h2>
                <?php if (in_array($user_role, ['admin', 'counsellor'])): ?>
                    <a href="add.php" class="btn btn-primary"><i class="bi bi-plus"></i> Add University</a>
                <?php endif; ?>
            </div>
            <form class="mb-3" method="get">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or country" value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Logo</th>
                                    <th>Name</th>
                                    <th>Country</th>
                                    <th>City</th>
                                    <th>Website</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($universities as $uni): ?>
                                    <tr>
                                        <td>
                                            <?php if ($uni['logo']): ?>
                                                <img src="<?= BASE_URL . '/' . htmlspecialchars($uni['logo']) ?>" alt="Logo" style="max-width:50px;max-height:50px;">
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($uni['name']) ?></td>
                                        <td><?= htmlspecialchars($uni['country']) ?></td>
                                        <td><?= htmlspecialchars($uni['city']) ?></td>
                                        <td>
                                            <?php if ($uni['website']): ?>
                                                <a href="<?= htmlspecialchars($uni['website']) ?>" target="_blank">Website</a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view.php?id=<?= urlencode($uni['id']) ?>" class="btn btn-sm btn-info">View</a>
                                            <?php if (in_array($user_role, ['admin', 'counsellor'])): ?>
                                                <a href="edit.php?id=<?= urlencode($uni['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="delete.php?id=<?= urlencode($uni['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this university?')">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($universities)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">No universities found.</td></tr>
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
                                <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
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