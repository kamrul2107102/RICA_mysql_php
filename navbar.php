<?php
// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-database me-2"></i>Research Paper DB
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="authors.php"><i class="fas fa-users me-1"></i> Authors</a></li>
                    <li class="nav-item"><a class="nav-link" href="papers.php"><i class="fas fa-file-alt me-1"></i> Papers</a></li>
                    <li class="nav-item"><a class="nav-link" href="journals.php"><i class="fas fa-book me-1"></i> Journals</a></li>
                    <li class="nav-item"><a class="nav-link" href="institutions.php"><i class="fas fa-building me-1"></i> Institutions</a></li>
                    <li class="nav-item"><a class="nav-link" href="conferences.php"><i class="fas fa-calendar-alt me-1"></i> Conferences</a></li>
                    <li class="nav-item"><a class="nav-link" href="analytics.php"><i class="fas fa-chart-bar me-1"></i> Analytics</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin.php"><i class="fas fa-cog me-1"></i> Admin</a></li>
                    <li class="nav-item">
                        <span class="nav-link text-warning">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                        </span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
