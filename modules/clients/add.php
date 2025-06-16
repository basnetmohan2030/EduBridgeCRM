<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) {
    header('Location: list.php');
    exit;
}
require_once '../../includes/db.php';
require_once '../../includes/mail.php';

$error = '';
$success = '';
$generated_password = '';
$client_email = '';

// After auth includes, add this to fetch counsellors
$counsellors_query = "SELECT id, first_name, last_name FROM counsellor ORDER BY first_name, last_name";
$counsellors_result = $mysqli->query($counsellors_query);
$counsellors = $counsellors_result->fetch_all(MYSQLI_ASSOC);

function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'other';
    $marital_status = $_POST['marital_status'] ?? 'single';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $handled_by = $_POST['handled_by'] ? intval($_POST['handled_by']) : null;

    if ($first_name && $last_name && $email) {
        // Check if user already exists
        $check_stmt = $mysqli->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $error = 'A user with this email already exists.';
        } else {
            $check_stmt->close();
            // Generate password and hash
            $generated_password = generateRandomPassword(10);
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
            // Generate UUID for user id
            $user_id = '';
            $uuid_stmt = $mysqli->query("SELECT UUID() AS uuid");
            if ($uuid_row = $uuid_stmt->fetch_assoc()) {
                $user_id = $uuid_row['uuid'];
            }
            // Insert into user table
            $user_role = 'client';
            $user_stmt = $mysqli->prepare('INSERT INTO user (id, name, email, password, email_verified, role, created_at, updated_at) VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())');
            $full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
            $user_stmt->bind_param('sssss', $user_id, $full_name, $email, $hashed_password, $user_role);
            if ($user_stmt->execute()) {
                // Insert into clients table
                $client_stmt = $mysqli->prepare('INSERT INTO clients (first_name, middle_name, last_name, email, phone, gender, marital_status, address, city, state, country, zip_code, accountId, handled_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
                $client_stmt->bind_param('sssssssssssssi', $first_name, $middle_name, $last_name, $email, $phone, $gender, $marital_status, $address, $city, $state, $country, $zip_code, $user_id, $handled_by);
                if ($client_stmt->execute()) {
                    $success = 'Client and user account created successfully!';
                    $client_email = $email;
                    // Send credentials email
                    $mail_sent = send_user_credentials($email, trim($first_name . ' ' . $last_name), $email, $generated_password);
                    if ($mail_sent) {
                        $success .= '<br><span class="text-success">Login credentials sent to <b>' . htmlspecialchars($email) . '</b>.</span>';
                    } else {
                        $success .= '<br><span class="text-danger">Failed to send email to <b>' . htmlspecialchars($email) . '</b>.</span>';
                    }
                } else {
                    $error = 'Failed to add client.';
                }
                $client_stmt->close();
            } else {
                $error = 'Failed to create user account.';
            }
            $user_stmt->close();
        }
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
    <title>Add Client - EduBridge CRM</title>
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
                <h2 class="mb-0">Add Client</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success">
                            <?= $success ?><br>
                            <strong>Login Email:</strong> <?= htmlspecialchars($client_email) ?><br>
                            <strong>Temporary Password:</strong> <span style="font-family:monospace;"><?= htmlspecialchars($generated_password) ?></span><br>
                            <span class="text-muted">Please provide these credentials to the client.</span><br>
                            <a href="list.php" class="btn btn-success mt-2">Back to List</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!$success): ?>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other" selected>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Marital Status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="single" selected>Single</option>
                                    <option value="married">Married</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" class="form-control">
                            </div>
                            <?php if (in_array($user_role, ['admin', 'counsellor'])): ?>
                            <div class="col-md-4">
                                <label class="form-label">Assigned Counsellor</label>
                                <select name="handled_by" class="form-select">
                                    <option value="">Select Counsellor</option>
                                    <?php foreach ($counsellors as $counsellor): ?>
                                        <option value="<?= $counsellor['id'] ?>"
                                            <?php if ($user_role === 'counsellor' && $counsellor['id'] == $counsellor_id): ?>
                                                selected
                                            <?php endif; ?>>
                                            <?= htmlspecialchars($counsellor['first_name'] . ' ' . $counsellor['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Add Client</button>
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