<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
$user_id = current_user_id();

require_once '../../includes/db.php';

// Get the correct client ID
$client_id = null;
if ($user_role === 'client') {
    // For clients, get their actual client ID from the clients table
    $stmt = $mysqli->prepare('SELECT id FROM clients WHERE accountId = ?');
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($client = $result->fetch_assoc()) {
        $client_id = $client['id'];
    }
    $stmt->close();
} else {
    $client_id = intval($_GET['client_id'] ?? 0);
}

// Check permissions
$can_add = false;
if ($user_role === 'admin') {
    $can_add = true;
} elseif ($user_role === 'counsellor') {
    // Check if client is assigned to this counsellor
    $check_sql = "SELECT c.id FROM clients c 
                  INNER JOIN counsellor co ON c.handled_by = co.id 
                  WHERE c.id = ? AND co.accountId = ?";
    $stmt = $mysqli->prepare($check_sql);
    $stmt->bind_param('is', $client_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $can_add = $result->num_rows > 0;
    $stmt->close();
} elseif ($user_role === 'client' && $client_id) {
    // Clients can add their own documents
    $can_add = true;
}

if (!$can_add || !$client_id) {
    header('Location: ' . ($user_role === 'client' ? 'view.php?id=' . $client_id : 'list.php'));
    exit('Unauthorized access');
}

$error = '';
$document_types = ['Academic Certificate', 'Transcript', 'ID Proof', 'Language Test Score', 'Resume/CV', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type = trim($_POST['document_type'] ?? '');
    $document_name = trim($_POST['document_name'] ?? '');
    
    if ($document_type && $document_name && !empty($_FILES['document_file']['name'])) {
        $file = $_FILES['document_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        
        if (in_array($ext, $allowed) && $file['size'] <= 5242880) { // 5MB limit
            $upload_dir = '../../uploads/documents/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = uniqid() . '_' . $file['name'];
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $document_url = 'uploads/documents/' . $filename;
                $stmt = $mysqli->prepare('INSERT INTO student_documents (client_id, document_type, document_name, document_url, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
                $stmt->bind_param('isss', $client_id, $document_type, $document_name, $document_url);
                $stmt->execute();
                $stmt->close();
                header('Location: view.php?id=' . $client_id . '&tab=documents');
                exit;
            } else {
                $error = 'Failed to upload file.';
            }
        } else {
            $error = 'Invalid file type or size. Allowed types: PDF, DOC, DOCX, JPG, PNG. Max size: 5MB';
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
    <title>Add Document - EduBridge CRM</title>
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
                <h2 class="mb-0">Add Document</h2>
                <a href="view.php?id=<?= $client_id ?>&tab=documents" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="post" enctype="multipart/form-data" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Document Type *</label>
                                <select name="document_type" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($document_types as $type): ?>
                                        <option value="<?= $type ?>"><?= $type ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Document Name *</label>
                                <input type="text" name="document_name" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Document File * (Max 5MB)</label>
                                <input type="file" name="document_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                <small class="text-muted">Allowed types: PDF, DOC, DOCX, JPG, PNG</small>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Add Document</button>
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