<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
$user_id = current_user_id();

// Only clients can access this page
if ($user_role !== 'client') {
    header('Location: list.php');
    exit('Unauthorized access');
}

require_once '../../includes/db.php';

// Fetch client profile
$stmt = $mysqli->prepare('SELECT * FROM clients WHERE accountId = ?');
$stmt->bind_param('s', $user_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$client) {
    header('Location: ../../dashboard.php');
    exit('Profile not found');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'other';
    $marital_status = $_POST['marital_status'] ?? 'single';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $profile_picture = $client['profile_picture'] ?? '';

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'profile_' . $user_id . '.' . $ext;
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

    if ($first_name && $last_name && !$error) {
        $stmt = $mysqli->prepare('UPDATE clients SET 
            first_name=?, middle_name=?, last_name=?, phone=?, 
            gender=?, marital_status=?, address=?, city=?, 
            state=?, country=?, zip_code=?, profile_picture=?, 
            updated_at=NOW() 
            WHERE accountId=?');
        $stmt->bind_param('sssssssssssss', 
            $first_name, $middle_name, $last_name, $phone,
            $gender, $marital_status, $address, $city,
            $state, $country, $zip_code, $profile_picture,
            $user_id
        );
        if ($stmt->execute()) {
            $success = 'Profile updated successfully.';
            // Refresh client data
            $stmt = $mysqli->prepare('SELECT * FROM clients WHERE accountId = ?');
            $stmt->bind_param('s', $user_id);
            $stmt->execute();
            $client = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update profile.';
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
    <title>My Profile - EduBridge CRM</title>
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
                <h2 class="mb-0">My Profile</h2>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <div class="text-center">
                                    <?php if (!empty($client['profile_picture'])): ?>
                                        <img src="<?= BASE_URL . htmlspecialchars($client['profile_picture']) ?>" class="profile-pic-preview mb-2" alt="Profile Picture">
                                    <?php endif; ?>
                                    <div>
                                        <label class="btn btn-outline-primary btn-sm">
                                            Change Profile Picture
                                            <input type="file" name="profile_picture" class="d-none" accept="image/*">
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($client['first_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($client['middle_name']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($client['last_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($client['email']) ?>" disabled>
                                <small class="text-muted">Contact administrator to change email</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($client['phone']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="male" <?= $client['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= $client['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= $client['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Marital Status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="single" <?= $client['marital_status'] === 'single' ? 'selected' : '' ?>>Single</option>
                                    <option value="married" <?= $client['marital_status'] === 'married' ? 'selected' : '' ?>>Married</option>
                                    <option value="other" <?= $client['marital_status'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($client['address']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($client['city']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($client['state']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($client['country']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" class="form-control" value="<?= htmlspecialchars($client['zip_code']) ?>">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
// Preview profile picture before upload
document.querySelector('input[name="profile_picture"]').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.profile-pic-preview');
            if (preview) {
                preview.src = e.target.result;
            } else {
                const newPreview = document.createElement('img');
                newPreview.src = e.target.result;
                newPreview.className = 'profile-pic-preview mb-2';
                newPreview.alt = 'Profile Picture';
                document.querySelector('input[name="profile_picture"]').parentElement.parentElement.prepend(newPreview);
            }
        }
        reader.readAsDataURL(this.files[0]);
    }
});
</script>
</body>
</html> 