<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
if (!defined('BASE_URL')) require_once '../../config.php';
$id = intval($_GET['id'] ?? 0);
$client_id = intval($_GET['client_id'] ?? 0);
if (!$id || !$client_id) { header('Location: list.php'); exit; }
$stmt = $mysqli->prepare('SELECT * FROM student_documents WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$doc) { header('Location: view.php?id=' . $client_id . '&tab=documents'); exit; }
$error = '';
$doc_types = ['Passport', 'Transcript', 'Certificate', 'Offer Letter', 'Visa', 'Photo', 'Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = trim($_POST['document_type'] ?? '');
    $document_name = trim($_POST['document_name'] ?? '');
    $new_file_uploaded = isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK;
    $document_url = $doc['document_url'];
    if ($document_type && $document_name) {
        if ($new_file_uploaded) {
            $ext = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('doc_') . '.' . $ext;
            $target = '../../uploads/' . $filename;
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target)) {
                // Delete old file
                if ($document_url && file_exists($_SERVER['DOCUMENT_ROOT'] . $document_url)) {
                    @unlink($_SERVER['DOCUMENT_ROOT'] . $document_url);
                }
                $document_url = '/uploads/' . $filename;
            } else {
                $error = 'File upload failed.';
            }
        }
        if (!$error) {
            $stmt = $mysqli->prepare('UPDATE student_documents SET document_type=?, document_name=?, document_url=?, updated_at=NOW() WHERE id=?');
            $stmt->bind_param('sssi', $document_type, $document_name, $document_url, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: view.php?id=' . $client_id . '&tab=documents'); exit;
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
    <title>Edit Document - EduBridge CRM</title>
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
                <h2 class="mb-0">Edit Document</h2>
                <a href="view.php?id=<?= $client_id ?>&tab=documents" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="post" enctype="multipart/form-data" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Document Type *</label>
                                <select name="document_type" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($doc_types as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $doc['document_type'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Document Name *</label>
                                <input type="text" name="document_name" class="form-control" value="<?= htmlspecialchars($doc['document_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Replace File</label>
                                <input type="file" name="document_file" class="form-control">
                                <?php if ($doc['document_url']): ?>
                                    <div class="mt-1"><a href="<?= BASE_URL . htmlspecialchars($doc['document_url']) ?>" target="_blank">Current File</a></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Document</button>
                            <a href="view.php?id=<?= $client_id ?>&tab=documents" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 