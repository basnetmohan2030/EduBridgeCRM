<?php
require_once '../../includes/auth.php';
require_login();
if (current_user_role() !== 'admin') { header('Location: ../clients/list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';

// Search/filter
$search = trim($_GET['search'] ?? '');
$where = $search ? "WHERE first_name LIKE CONCAT('%', ?, '%') OR last_name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%')" : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

if ($where) {
    $stmt = $mysqli->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM counsellor $where ORDER BY created_at DESC LIMIT ?, ?");
    $stmt->bind_param('ssii', $search, $search, $search, $offset, $per_page);
} else {
    $stmt = $mysqli->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM counsellor ORDER BY created_at DESC LIMIT ?, ?");
    $stmt->bind_param('ii', $offset, $per_page);
}
$stmt->execute();
$counsellors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$total = $mysqli->query('SELECT FOUND_ROWS()')->fetch_row()[0];
$total_pages = ceil($total / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counsellors - EduBridge CRM</title>
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
                <h2 class="mb-0">Counsellors</h2>
                <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Counsellor</a>
            </div>
            <form class="mb-3" method="get">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
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
                                    <th>Gender</th>
                                    <th>City</th>
                                    <th>Country</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($counsellors)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No counsellors found.</td></tr>
                                <?php else: ?>
                                <?php foreach ($counsellors as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(trim($c['first_name'] . ' ' . $c['middle_name'] . ' ' . $c['last_name'])) ?></td>
                                        <td><?= htmlspecialchars($c['email']) ?></td>
                                        <td><?= htmlspecialchars($c['phone']) ?></td>
                                        <td><?= htmlspecialchars($c['gender']) ?></td>
                                        <td><?= htmlspecialchars($c['city']) ?></td>
                                        <td><?= htmlspecialchars($c['country']) ?></td>
                                        <td>
                                            <a href="view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                            <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                            <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this counsellor?');"><i class="bi bi-trash"></i></a>
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
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
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