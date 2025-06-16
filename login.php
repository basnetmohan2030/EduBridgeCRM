<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $mysqli->prepare('SELECT id, name, email, password, role, banned FROM user WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                if (!empty($user['banned'])) {
                    $error = 'Your account is banned.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    // Fetch name from clients/counsellor if not admin
                    if ($user['role'] === 'client') {
                        $cstmt = $mysqli->prepare('SELECT first_name, middle_name, last_name FROM clients WHERE accountId = ? LIMIT 1');
                        $cstmt->bind_param('s', $user['id']);
                        $cstmt->execute();
                        $cres = $cstmt->get_result();
                        if ($c = $cres->fetch_assoc()) {
                            $full_name = trim($c['first_name'] . ' ' . $c['middle_name'] . ' ' . $c['last_name']);
                            $_SESSION['user_name'] = $full_name;
                        } else {
                            $_SESSION['user_name'] = $user['name'];
                        }
                        $cstmt->close();
                    } else if ($user['role'] === 'counsellor') {
                        $cstmt = $mysqli->prepare('SELECT first_name, middle_name, last_name FROM counsellor WHERE accountId = ? LIMIT 1');
                        $cstmt->bind_param('s', $user['id']);
                        $cstmt->execute();
                        $cres = $cstmt->get_result();
                        if ($c = $cres->fetch_assoc()) {
                            $full_name = trim($c['first_name'] . ' ' . $c['middle_name'] . ' ' . $c['last_name']);
                            $_SESSION['user_name'] = $full_name;
                        } else {
                            $_SESSION['user_name'] = $user['name'];
                        }
                        $cstmt->close();
                    } else {
                        $_SESSION['user_name'] = $user['name'];
                    }
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
        $stmt->close();
    } else {
        $error = 'Please enter both email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduBridge CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Login</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <div class="mt-3 text-center">
                        Don't have an account? <a href="register.php">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 