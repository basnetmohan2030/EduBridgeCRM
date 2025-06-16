<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';
require_once '../../includes/mail.php';

$error = '';
$success = '';
$generated_password = '';
$client_email = '';
$test_result = '';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: list.php'); exit; }

// Fetch inquiry
$stmt = $mysqli->prepare('SELECT * FROM inquiries WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$inq = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$inq) { header('Location: list.php'); exit; }

if ($inq['status'] === 'converted') {
    $error = 'This inquiry has already been converted to a client.';
}

if (!$error && !empty($inq['email'])) {
    // Check if client or user with this email already exists
    $stmt = $mysqli->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $inq['email']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = 'A user/client with this email already exists.';
    }
    $stmt->close();
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create user account
    $generated_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, 10);
    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
    $user_id = bin2hex(random_bytes(16));
    $full_name = $inq['name'];
    $stmt = $mysqli->prepare('INSERT INTO user (id, name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, "client", NOW(), NOW())');
    $stmt->bind_param('ssss', $user_id, $full_name, $inq['email'], $hashed_password);
    if ($stmt->execute()) {
        $stmt->close();
        // Insert into clients
        $stmt2 = $mysqli->prepare('INSERT INTO clients (first_name, last_name, email, phone, accountId, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $name_parts = explode(' ', $inq['name'], 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        $stmt2->bind_param('sssss', $first_name, $last_name, $inq['email'], $inq['phone'], $user_id);
        if ($stmt2->execute()) {
            $success = 'Client created successfully.';
            $client_email = $inq['email'];
            // Update inquiry status
            $mysqli->query("UPDATE inquiries SET status='converted' WHERE id=$id");
            // Send credentials email
            $mail_sent = send_user_credentials($client_email, $full_name, $client_email, $generated_password);
            if ($mail_sent) {
                $success .= '<br><span class="text-success">Login credentials sent to <b>' . htmlspecialchars($client_email) . '</b>.</span>';
            } else {
                $success .= '<br><span class="text-danger">Failed to send email to <b>' . htmlspecialchars($client_email) . '</b>.</span>';
            }
        } else {
            $error = 'Failed to create client.';
        }
        $stmt2->close();
    } else {
        $error = 'Failed to create user account.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convert to Client - EduBridge CRM</title>
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
                <h2 class="mb-0">Convert to Client</h2>
                <a href="view.php?id=<?= $id ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Inquiry</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success">Client created successfully.</div>
                        <div class="mt-3"><strong>Login Credentials:</strong><br>
                            <div class="input-group mb-2"><input type="text" class="form-control" value="Email: <?= htmlspecialchars($client_email) ?> | Password: <?= htmlspecialchars($generated_password) ?>" id="credInput" readonly><button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('credInput').value)"><i class="bi bi-clipboard"></i> Copy</button></div>
                        </div>
                    <?php else: ?>
                        <form method="post">
                            <div class="alert alert-info">Are you sure you want to convert this inquiry to a client? This will create a new client and user account.</div>
                            <button type="submit" class="btn btn-success">Convert to Client</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 