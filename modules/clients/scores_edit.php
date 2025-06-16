<?php
require_once '../../includes/auth.php';
require_login();
$user_role = current_user_role();
if (!in_array($user_role, ['admin', 'counsellor'])) { header('Location: list.php'); exit; }
require_once '../../includes/db.php';
$id = intval($_GET['id'] ?? 0);
$client_id = intval($_GET['client_id'] ?? 0);
if (!$id || !$client_id) { header('Location: list.php'); exit; }
$stmt = $mysqli->prepare('SELECT * FROM student_test_scores WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$score = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$score) { header('Location: view.php?id=' . $client_id . '&tab=scores'); exit; }
$error = '';
$exam_types = ['IELTS', 'TOEFL', 'GRE', 'GMAT', 'SAT', 'PTE', 'Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_type = trim($_POST['exam_type'] ?? '');
    $date_of_exam = trim($_POST['date_of_exam'] ?? '');
    $overall_score = intval($_POST['overall_score'] ?? 0);
    $listening = trim($_POST['listening'] ?? '');
    $reading = trim($_POST['reading'] ?? '');
    $writing = trim($_POST['writing'] ?? '');
    $speaking = trim($_POST['speaking'] ?? '');
    $gre_score = $_POST['gre_score'] !== '' ? intval($_POST['gre_score']) : null;
    $gmat_score = $_POST['gmat_score'] !== '' ? intval($_POST['gmat_score']) : null;
    $sat_score = $_POST['sat_score'] !== '' ? intval($_POST['sat_score']) : null;
    if ($exam_type && $date_of_exam && $overall_score && $listening && $reading && $writing && $speaking) {
        $stmt = $mysqli->prepare('UPDATE student_test_scores SET exam_type=?, date_of_exam=?, overall_score=?, listening=?, reading=?, writing=?, speaking=?, gre_score=?, gmat_score=?, sat_score=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('ssisssssiiii', $exam_type, $date_of_exam, $overall_score, $listening, $reading, $writing, $speaking, $gre_score, $gmat_score, $sat_score, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: view.php?id=' . $client_id . '&tab=scores'); exit;
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
    <title>Edit Test Score - EduBridge CRM</title>
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
                <h2 class="mb-0">Edit Test Score</h2>
                <a href="view.php?id=<?= $client_id ?>&tab=scores" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Exam Type *</label>
                                <select name="exam_type" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($exam_types as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $score['exam_type'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date of Exam *</label>
                                <input type="date" name="date_of_exam" class="form-control" value="<?= htmlspecialchars($score['date_of_exam']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Overall Score *</label>
                                <input type="number" name="overall_score" class="form-control" value="<?= htmlspecialchars($score['overall_score']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Listening *</label>
                                <input type="text" name="listening" class="form-control" value="<?= htmlspecialchars($score['listening']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reading *</label>
                                <input type="text" name="reading" class="form-control" value="<?= htmlspecialchars($score['reading']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Writing *</label>
                                <input type="text" name="writing" class="form-control" value="<?= htmlspecialchars($score['writing']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Speaking *</label>
                                <input type="text" name="speaking" class="form-control" value="<?= htmlspecialchars($score['speaking']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">GRE Score</label>
                                <input type="number" name="gre_score" class="form-control" value="<?= htmlspecialchars($score['gre_score']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">GMAT Score</label>
                                <input type="number" name="gmat_score" class="form-control" value="<?= htmlspecialchars($score['gmat_score']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SAT Score</label>
                                <input type="number" name="sat_score" class="form-control" value="<?= htmlspecialchars($score['sat_score']) ?>">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Update Test Score</button>
                            <a href="view.php?id=<?= $client_id ?>&tab=scores" class="btn btn-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html> 