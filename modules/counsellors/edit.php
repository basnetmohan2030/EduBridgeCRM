<?php
require_once '../../includes/auth.php';
require_login();
if (current_user_role() !== 'admin') { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: list.php'); exit; }

$error = '';
$success = '';

// Fetch counsellor data
$stmt = $mysqli->prepare('SELECT * FROM counsellor WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$counsellor = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$counsellor) { header('Location: list.php'); exit; }

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
    $profile_picture = $counsellor['profile_picture'] ?? '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'counsellor_' . $id . '.' . $ext;
            $target = '../../uploads/' . $filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
                // Delete old file if exists and different
                if ($profile_picture && $profile_picture !== 'uploads/' . $filename && file_exists('../../' . $profile_picture)) {
                    @unlink('../../' . $profile_picture);
                }
                $profile_picture = 'uploads/' . $filename;
            } else {
                $error = 'Profile picture upload failed.';
            }
        } else {
            $error = 'Invalid image format. Only jpg, jpeg, png, gif allowed.';
        }
    }
    if ($first_name && $last_name && $email && !$error) {
        $stmt = $mysqli->prepare('UPDATE counsellor SET first_name=?, middle_name=?, last_name=?, dob=?, nationality=?, email=?, phone=?, gender=?, address=?, city=?, state=?, country=?, zip_code=?, profile_picture=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('ssssssssssssssi', $first_name, $middle_name, $last_name, $dob, $nationality, $email, $phone, $gender, $address, $city, $state, $country, $zip_code, $profile_picture, $id);
        if ($stmt->execute()) {
            header('Location: list.php'); exit;
        } else {
            $error = 'Failed to update counsellor.';
        }
        $stmt->close();
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
    <title>Edit Counsellor - EduBridge CRM</title>
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
                <h2 class="mb-0">Edit Counsellor</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($counsellor['first_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($counsellor['middle_name']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($counsellor['last_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($counsellor['dob']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?= htmlspecialchars($counsellor['nationality']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($counsellor['email']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($counsellor['phone']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="male" <?= $counsellor['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= $counsellor['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= $counsellor['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($counsellor['address']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($counsellor['city']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($counsellor['state']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($counsellor['country']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" class="form-control" value="<?= htmlspecialchars($counsellor['zip_code']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                <?php if (!empty($counsellor['profile_picture'])): ?>
                                    <div class="mt-2">
                                        <img src="<?= BASE_URL . htmlspecialchars($counsellor['profile_picture']) ?>" class="profile-pic-preview" alt="Profile Picture">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Counsellor</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 