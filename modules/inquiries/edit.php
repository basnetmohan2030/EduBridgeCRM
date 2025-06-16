<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: list.php'); exit; }

$error = '';

// Get counsellors for assignment
$counsellors = [];
$res = $mysqli->query("SELECT id, first_name, last_name FROM counsellor ORDER BY first_name");
while ($row = $res->fetch_assoc()) $counsellors[$row['id']] = trim($row['first_name'] . ' ' . $row['last_name']);

$status_options = ['new'=>'New','contacted'=>'Contacted','in_progress'=>'In Progress','converted'=>'Converted','closed'=>'Closed','lost'=>'Lost'];
$source_options = ['Website','Walk-in','Facebook','Referral','Phone','Other'];

// Fetch inquiry
$stmt = $mysqli->prepare('SELECT * FROM inquiries WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$inq = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$inq) { header('Location: list.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $source = trim($_POST['source'] ?? '');
    $status = $_POST['status'] ?? 'new';
    $assigned_to = $user_role === 'admin' ? ($_POST['assigned_to'] ?? null) : $inq['assigned_to'];
    $message = trim($_POST['message'] ?? '');
    if ($name && $status && !$error) {
        $stmt = $mysqli->prepare('UPDATE inquiries SET name=?, email=?, phone=?, source=?, status=?, assigned_to=?, message=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('sssssssi', $name, $email, $phone, $source, $status, $assigned_to, $message, $id);
        if ($stmt->execute()) {
            header('Location: list.php'); exit;
        } else {
            $error = 'Failed to update inquiry.';
        }
        $stmt->close();
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
    <title>Edit Inquiry - EduBridge CRM</title>
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
                <h2 class="mb-0">Edit Inquiry</h2>
                <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($inq['name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($inq['email']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($inq['phone']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Source</label>
                                <select name="source" class="form-select">
                                    <option value="">Select</option>
                                    <?php foreach ($source_options as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $inq['source']===$opt?'selected':'' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach ($status_options as $k=>$v): ?>
                                        <option value="<?= $k ?>" <?= $inq['status']===$k?'selected':'' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($user_role === 'admin'): ?>
                            <div class="col-md-4">
                                <label class="form-label">Assign To</label>
                                <select name="assigned_to" class="form-select">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($counsellors as $cid=>$cname): ?>
                                        <option value="<?= $cid ?>" <?= $inq['assigned_to']==$cid?'selected':'' ?>><?= htmlspecialchars($cname) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-12">
                                <label class="form-label">Message/Notes</label>
                                <textarea name="message" class="form-control" rows="3"><?= htmlspecialchars($inq['message']) ?></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Inquiry</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 