<?php if (!defined('BASE_URL')) require_once __DIR__ . '/../config.php'; ?>
<?php $current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); ?>
<?php $is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'); ?>
<?php $is_counsellor = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'counsellor'); ?>
<?php $is_client = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client'); ?>
<nav class="col-md-2 d-none d-md-block sidebar py-4">
    <div class="position-sticky">
        <ul class="nav flex-column">
            <?php if (!$is_client): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>dashboard.php">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
            </li>
            <?php endif; ?>
            <?php if ($is_admin || $is_counsellor): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>modules/inquiries/list.php">
                    <i class="bi bi-question-circle"></i> Inquiries
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>modules/applications/list.php">
                    <i class="bi bi-journal-text"></i> <?= $is_client ? 'My Applications' : 'Applications' ?>
                </a>
            </li>
            <?php if ($is_admin || $is_counsellor): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>modules/clients/list.php">
                    <i class="bi bi-people"></i> Clients
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>modules/clients/view.php?id=<?= $_SESSION['user_id'] ?>">
                    <i class="bi bi-person"></i> My Profile
                </a>
            </li>
            <?php endif; ?>
            <?php if ($is_admin): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>modules/counsellors/list.php">
                    <i class="bi bi-person-badge"></i> Counsellors
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>modules/universities/list.php">
                    <i class="bi bi-mortarboard"></i> Universities
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>modules/programs/list.php">
                    <i class="bi bi-collection"></i> Programs
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="<?= BASE_URL ?>logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
<script>
// Real-time active menu highlighting
(function() {
    var links = document.querySelectorAll('.sidebar .nav-link');
    var current = window.location.pathname.replace(/\/+$/, '');
    links.forEach(function(link) {
        var href = link.getAttribute('href');
        if (!href) return;
        var linkPath = document.createElement('a');
        linkPath.href = href;
        if (linkPath.pathname.replace(/\/+$/, '') === current) {
            link.classList.add('active');
        }
    });
})();
</script> 