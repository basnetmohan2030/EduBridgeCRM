<?php
require_once 'includes/auth.php';
require_login();
$user_role = current_user_role();
$user_id = current_user_id();

// Redirect clients to their profile page
if ($user_role === 'client') {
    // First get the client's ID from their accountId
    require_once 'includes/db.php';
    $stmt = $mysqli->prepare('SELECT id FROM clients WHERE accountId = ? LIMIT 1');
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($client = $result->fetch_assoc()) {
        // Convert ID to string for consistency
        $client_id = (string)$client['id'];
        header('Location: modules/clients/view.php?id=' . $client_id);
        exit;
    }
    $stmt->close();
}

require_once 'includes/db.php';

// Total counts
$total_clients = $mysqli->query('SELECT COUNT(*) FROM clients')->fetch_row()[0];
$total_counsellors = $mysqli->query('SELECT COUNT(*) FROM counsellor')->fetch_row()[0];
$total_inquiries = $mysqli->query('SELECT COUNT(*) FROM inquiries')->fetch_row()[0];
$total_applications = $mysqli->query('SELECT COUNT(*) FROM applications')->fetch_row()[0];

// Applications by status
$app_statuses = ['Draft','Submitted','In Review','Accepted','Rejected','Withdrawn'];
$app_status_counts = [];
$stmt = $mysqli->prepare('SELECT status, COUNT(*) as cnt FROM applications GROUP BY status');
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $app_status_counts[$row['status']] = $row['cnt'];
}
$stmt->close();
foreach ($app_statuses as $s) {
    if (!isset($app_status_counts[$s])) $app_status_counts[$s] = 0;
}

// Top universities by applications
$top_universities = $mysqli->query('SELECT u.name, COUNT(a.id) as total FROM applications a JOIN universities u ON a.university_id = u.id GROUP BY a.university_id ORDER BY total DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
// Top programs by applications
$top_programs = $mysqli->query('SELECT p.name, COUNT(a.id) as total FROM applications a JOIN programs p ON a.program_id = p.id GROUP BY a.program_id ORDER BY total DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
// Recent activity (last 10 applications)
$recent_apps = $mysqli->query('SELECT a.application_number, c.first_name, c.last_name, u.name AS university_name, p.name AS program_name, a.status, a.created_at FROM applications a JOIN clients c ON a.client_id = c.accountId JOIN universities u ON a.university_id = u.id JOIN programs p ON a.program_id = p.id ORDER BY a.created_at DESC LIMIT 10')->fetch_all(MYSQLI_ASSOC);
// Recent inquiries (last 10)
$recent_inquiries = $mysqli->query('SELECT i.id, i.name, i.email, i.status, i.created_at FROM inquiries i ORDER BY i.created_at DESC LIMIT 10')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Nexsus CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
<?php include 'includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Dashboard</h2>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card text-bg-primary shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Total Clients</h5>
                            <div class="display-6 fw-bold"><?= $total_clients ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Total Counsellors</h5>
                            <div class="display-6 fw-bold"><?= $total_counsellors ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Total Inquiries</h5>
                            <div class="display-6 fw-bold"><?= $total_inquiries ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-info shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Total Applications</h5>
                            <div class="display-6 fw-bold"><?= $total_applications ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Applications by Status</h5>
                            <canvas id="appStatusChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Top Universities by Applications</h5>
                            <ol class="mb-0">
                                <?php foreach ($top_universities as $u): ?>
                                    <li><?= htmlspecialchars($u['name']) ?> <span class="badge bg-primary ms-2"><?= $u['total'] ?></span></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Top Programs by Applications</h5>
                            <ol class="mb-0">
                                <?php foreach ($top_programs as $p): ?>
                                    <li><?= htmlspecialchars($p['name']) ?> <span class="badge bg-info ms-2"><?= $p['total'] ?></span></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Recent Applications</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>App #</th><th>Client</th><th>University</th><th>Program</th><th>Status</th><th>Date</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($recent_apps as $a): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($a['application_number']) ?></td>
                                                <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                                <td><?= htmlspecialchars($a['university_name']) ?></td>
                                                <td><?= htmlspecialchars($a['program_name']) ?></td>
                                                <td><?= htmlspecialchars($a['status']) ?></td>
                                                <td><?= htmlspecialchars($a['created_at']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Recent Inquiries</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Date</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($recent_inquiries as $i): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($i['id']) ?></td>
                                                <td><?= htmlspecialchars($i['name']) ?></td>
                                                <td><?= htmlspecialchars($i['email']) ?></td>
                                                <td><?= htmlspecialchars($i['status']) ?></td>
                                                <td><?= htmlspecialchars($i['created_at']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
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
<script>
const ctx = document.getElementById('appStatusChart').getContext('2d');
const appStatusChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($app_statuses) ?>,
        datasets: [{
            label: 'Applications',
            data: <?= json_encode(array_values($app_status_counts)) ?>,
            backgroundColor: [
                '#0d6efd','#198754','#ffc107','#0dcaf0','#dc3545','#6c757d'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } }
    }
});
</script>
</body>
</html> 