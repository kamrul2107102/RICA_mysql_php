<?php
// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-database me-2"></i>Research Paper DB
        </a>

        <!-- Toggler for mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">

                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'authors.php' ? 'active' : '' ?>" href="authors.php">
                            <i class="fas fa-users me-1"></i> Authors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'papers.php' ? 'active' : '' ?>" href="papers.php">
                            <i class="fas fa-file-alt me-1"></i> Papers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'journals.php' ? 'active' : '' ?>" href="journals.php">
                            <i class="fas fa-book me-1"></i> Journals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'institutions.php' ? 'active' : '' ?>" href="institutions.php">
                            <i class="fas fa-building me-1"></i> Institutions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'conferences.php' ? 'active' : '' ?>" href="conferences.php">
                            <i class="fas fa-calendar-alt me-1"></i> Conferences
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                            <i class="fas fa-chart-bar me-1"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'admin.php' ? 'active' : '' ?>" href="admin.php">
                            <i class="fas fa-cog me-1"></i> Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-warning">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['admin_username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'login.php' ? 'active' : '' ?>" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Theme toggle button -->
                <li class="nav-item ms-3">
                    <button id="theme-toggle" class="btn btn-sm btn-light" title="Toggle Theme">
                        <span id="theme-icon">🌙</span>
                    </button>
                </li>

            </ul>
        </div>
    </div>
</nav>

<!-- Include the theme toggle JS -->
<script src="js/theme.js"></script>
