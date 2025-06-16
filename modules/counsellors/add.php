<?php
require_once '../../includes/auth.php';
require_login();
if (current_user_role() !== 'admin') { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
require_once '../../includes/mail.php';
if (!defined('BASE_URL')) require_once '../../config.php';

$error = '';
$success = '';
$generated_password = '';

function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'other';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $profile_picture = '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'counsellor_' . uniqid() . '.' . $ext;
            $target = '../../uploads/' . $filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
                $profile_picture = 'uploads/' . $filename;
            } else {
                $error = 'Profile picture upload failed.';
            }
        } else {
            $error = 'Invalid image format. Only jpg, jpeg, png, gif allowed.';
        }
    }
    // Check if email already exists
    if (!$error) {
        $stmt = $mysqli->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'A user with this email already exists.';
        }
        $stmt->close();
    }
    if ($first_name && $last_name && $email && !$error) {
        // Create user account
        $generated_password = generateRandomPassword(10);
        $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
        $user_id = bin2hex(random_bytes(16));
        $stmt = $mysqli->prepare('INSERT INTO user (id, name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, "counsellor", NOW(), NOW())');
        $full_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
        $stmt->bind_param('ssss', $user_id, $full_name, $email, $hashed_password);
        if ($stmt->execute()) {
            $stmt->close();
            // Insert counsellor
            $stmt2 = $mysqli->prepare('INSERT INTO counsellor (first_name, middle_name, last_name, dob, nationality, email, phone, gender, address, city, state, country, zip_code, accountId, profile_picture, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt2->bind_param('sssssssssssssss', $first_name, $middle_name, $last_name, $dob, $nationality, $email, $phone, $gender, $address, $city, $state, $country, $zip_code, $user_id, $profile_picture);
            if ($stmt2->execute()) {
                $success = 'Counsellor added successfully.';
                $success .= '<div class="mt-3"><strong>Login Credentials:</strong><br>';
                $success .= '<div class="input-group mb-2"><input type="text" class="form-control" value="Email: ' . htmlspecialchars($email) . ' | Password: ' . htmlspecialchars($generated_password) . '" id="credInput" readonly><button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'credInput\').value)"><i class="bi bi-clipboard"></i> Copy</button></div>';
                $success .= '</div>';
                // Send credentials email
                $mail_sent = send_user_credentials($email, trim($first_name . ' ' . $last_name), $email, $generated_password);
                if ($mail_sent) {
                    $success .= '<br><span class="text-success">Login credentials sent to <b>' . htmlspecialchars($email) . '</b>.</span>';
                } else {
                    $success .= '<br><span class="text-danger">Failed to send email to <b>' . htmlspecialchars($email) . '</b>.</span>';
                }
            } else {
                $error = 'Failed to add counsellor.';
            }
            $stmt2->close();
        } else {
            $error = 'Failed to create user account. Email may already exist.';
        }
    } else if (!$error) {
        $error = 'Please fill all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Counsellor - EduBridge CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; }
        .sidebar { min-height: 100vh; background: #e3e9f7; box-shadow: 2px 0 8px rgba(0,0,0,0.03); }
        .sidebar .nav-link { color: #495057; font-weight: 500; border-radius: 0.375rem; margin-bottom: 0.5rem; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #c7d2fe; color: #1d3557; }
        .header { background: #f1f5fa; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .user-info { color: #495057; }
        .profile-pic-preview { max-width: 120px; max-height: 120px; border-radius: 50%; object-fit: cover; }
    </style>
</head>
<body>
<?php include '../../includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Add Counsellor</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" autocomplete="off">
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
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control">
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
                            <div class="col-md-4">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Add Counsellor</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 