<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'client';

    if ($name && $email && $password) {
        $stmt = $mysqli->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            $stmt->close();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_id = '';
            $uuid_stmt = $mysqli->query("SELECT UUID() AS uuid");
            if ($uuid_row = $uuid_stmt->fetch_assoc()) {
                $user_id = $uuid_row['uuid'];
            }
            $stmt = $mysqli->prepare('INSERT INTO user (id, name, email, password, email_verified, role, created_at, updated_at) VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())');
            $stmt->bind_param('sssss', $user_id, $name, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                // Split name for clients table
                $name_parts = explode(' ', $name, 3);
                $first_name = $name_parts[0] ?? '';
                $middle_name = isset($name_parts[2]) ? $name_parts[1] : '';
                $last_name = $name_parts[2] ?? ($name_parts[1] ?? '');
                $client_stmt = $mysqli->prepare('INSERT INTO clients (first_name, middle_name, last_name, email, accountId, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
                $client_stmt->bind_param('sssss', $first_name, $middle_name, $last_name, $email, $user_id);
                $client_stmt->execute();
                $client_stmt->close();
                $success = 'Registration successful! You can now <a href="login.php">login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    } else {
        $error = 'Please fill all fields correctly.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EduBridge CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Register</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    <div class="mt-3 text-center">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 