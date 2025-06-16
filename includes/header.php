<?php
if (!isset($user_name)) $user_name = htmlspecialchars(current_user_name() ?? '');
if (!isset($user_role)) $user_role = htmlspecialchars(current_user_role() ?? '');
?>
<header class="header py-3 px-4 d-flex justify-content-between align-items-center">
    <div class="h4 mb-0" style="color:#2d3a4a;">EduBridge CRM</div>
    <div class="user-info">
        <span class="me-3">Welcome, <strong><?= $user_name ?></strong> (<?= $user_role ?>)</span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
</header> 