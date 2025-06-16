<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) {
    header('Location: list.php');
    exit;
}
require_once '../../includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: list.php');
    exit;
}

// Fetch university
$stmt = $mysqli->prepare('SELECT * FROM universities WHERE id = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$uni = $result->fetch_assoc();
$stmt->close();
if (!$uni) {
    header('Location: list.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $logo = $uni['logo'];

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'university_' . uniqid() . '.' . $ext;
            $target = '../../uploads/' . $filename;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                // Delete old logo if exists
                if ($logo && file_exists('../../' . $logo)) {
                    @unlink('../../' . $logo);
                }
                $logo = 'uploads/' . $filename;
            } else {
                $error = 'Logo upload failed.';
            }
        } else {
            $error = 'Invalid image format. Only jpg, jpeg, png, gif allowed.';
        }
    }

    if ($name && !$error) {
        $stmt = $mysqli->prepare('UPDATE universities SET name=?, country=?, city=?, website=?, description=?, logo=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('sssssss', $name, $country, $city, $website, $description, $logo, $id);
        if ($stmt->execute()) {
            $success = 'University updated successfully!';
            // Refresh data
            $uni = array_merge($uni, [
                'name' => $name,
                'country' => $country,
                'city' => $city,
                'website' => $website,
                'description' => $description,
                'logo' => $logo
            ]);
        } else {
            $error = 'Failed to update university.';
        }
        $stmt->close();
    } elseif (!$error) {
        $error = 'Please fill all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit University - EduBridge CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../../includes/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Edit University</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?= $success ?><br>
                            <a href="list.php" class="btn btn-success mt-2">Back to List</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!$success): ?>
                    <form method="post" enctype="multipart/form-data" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($uni['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($uni['country']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($uni['city']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($uni['website']) ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($uni['description']) ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Logo</label><br>
                                <?php if ($uni['logo']): ?>
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($uni['logo']) ?>" alt="Logo" style="max-width:80px;max-height:80px;" class="mb-2"><br>
                                <?php endif; ?>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                                <small class="text-muted">Leave blank to keep current logo.</small>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update University</button>
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