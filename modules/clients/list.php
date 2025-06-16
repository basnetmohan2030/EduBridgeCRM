<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
$user_id = current_user_id();

// Redirect clients to their profile page
if ($user_role === 'client') {
    header('Location: view.php?id=' . $user_id);
    exit;
}

$user_name = htmlspecialchars(current_user_name());
require_once '../../includes/db.php';

// Add role-based query modification
$base_where = '';
if ($user_role === 'counsellor') {
    // Get counsellor ID
    $counsellor_id_query = "SELECT id FROM counsellor WHERE accountId = ?";
    $stmt = $mysqli->prepare($counsellor_id_query);
    $stmt->bind_param('s', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $counsellor = $result->fetch_assoc();
    $counsellor_id = $counsellor['id'];
    $stmt->close();
    
    $base_where = "WHERE c.handled_by = " . intval($counsellor_id);
}

// Search/filter
$search = trim($_GET['search'] ?? '');
$where = $base_where;
$params = [];
$types = '';
if ($search !== '') {
    $where .= ($where ? " AND " : "WHERE ") . 
              "(CONCAT(c.first_name, ' ', c.middle_name, ' ', c.last_name) LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $search_param = '%' . $search . '%';
    $params = [$search_param, $search_param, $search_param];
    $types = 'sss';
}

// Pagination
$per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Count total
$count_sql = "SELECT COUNT(*) FROM clients c 
              INNER JOIN user u ON c.accountId = u.id 
              LEFT JOIN counsellor co ON c.handled_by = co.id " . 
              $where;
$count_stmt = $mysqli->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_stmt->bind_result($total_clients);
$count_stmt->fetch();
$count_stmt->close();
$total_pages = max(1, ceil($total_clients / $per_page));

// Fetch clients
$sql = "SELECT c.id, c.first_name, c.middle_name, c.last_name, c.email, c.phone, c.handled_by,
        CONCAT(co.first_name, ' ', co.last_name) as counsellor_name 
        FROM clients c
        INNER JOIN user u ON c.accountId = u.id 
        LEFT JOIN counsellor co ON c.handled_by = co.id " .
        $where . " 
        ORDER BY c.created_at DESC LIMIT ? OFFSET ?";

if ($where) {
    $all_params = [...$params, $per_page, $offset];
    $all_types = $types . 'ii';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($all_types, ...$all_params);
} else {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - EduBridge CRM</title>
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
                <h2 class="mb-0">Clients</h2>
                <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Client</a>
            </div>
            <form class="mb-3" method="get" action="">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search by name, email, or phone" value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Counsellor</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clients)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">No clients found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(trim($client['first_name'] . ' ' . $client['middle_name'] . ' ' . $client['last_name'])) ?></td>
                                            <td><?= htmlspecialchars($client['email']) ?></td>
                                            <td><?= htmlspecialchars($client['phone']) ?></td>
                                            <td><?= htmlspecialchars($client['counsellor_name']) ?></td>
                                            <td>
                                                <a href="view.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                                <?php if ($user_role === 'admin' || ($user_role === 'counsellor' && $client['handled_by'] == $counsellor_id)): ?>
                                                <a href="edit.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                <?php endif; ?>
                                                <?php if ($user_role === 'admin'): ?>
                                                <a href="delete.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this client?');"><i class="bi bi-trash"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center mt-3">
                                <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 