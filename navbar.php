<?php
// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .modern-navbar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        padding: 0.6rem 0;
        position: sticky;
        top: 0;
        z-index: 1030;
        backdrop-filter: blur(10px);
    }
    
    .modern-navbar .navbar-brand {
        font-size: 1.5rem;
        font-weight: 700;
        color: white !important;
        transition: all 0.3s ease;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        margin-right: auto !important;
    }
    
    .modern-navbar .navbar-brand:hover {
        transform: scale(1.05);
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    .modern-navbar .navbar-brand i {
        background: white;
        color: #667eea;
        padding: 0.4rem;
        border-radius: 8px;
        margin-right: 0.5rem;
    }
    
    .modern-navbar .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 500;
        padding: 0.5rem 0.8rem !important;
        margin: 0 0.1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        font-size: 0.95rem;
    }
    
    .modern-navbar .nav-link::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: white;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    
    .modern-navbar .nav-link:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }
    
    .modern-navbar .nav-link:hover::before {
        width: 80%;
    }
    
    .modern-navbar .nav-link.active {
        background: rgba(255, 255, 255, 0.25) !important;
        color: white !important;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .modern-navbar .nav-link.active::before {
        width: 80%;
    }
    
    .modern-navbar .nav-link i {
        margin-right: 0.4rem;
        font-size: 1rem;
    }
    
    .user-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
        font-weight: 600 !important;
        padding: 0.5rem 1rem !important;
        border-radius: 20px !important;
        box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
        border: 2px solid rgba(255, 255, 255, 0.5);
        font-size: 0.9rem;
    }
    
    .user-badge i {
        color: #ffd700;
        font-size: 0.9rem;
    }
    
    .logout-btn {
        background: rgba(220, 53, 69, 0.9) !important;
        color: white !important;
        font-weight: 600 !important;
        padding: 0.5rem 1rem !important;
        border-radius: 20px !important;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        font-size: 0.9rem;
    }
    
    .logout-btn:hover {
        background: rgba(220, 53, 69, 1) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(220, 53, 69, 0.4);
    }
    
    .login-btn {
        background: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        font-weight: 600 !important;
        padding: 0.6rem 1.5rem !important;
        border-radius: 20px !important;
        border: 2px solid white !important;
        transition: all 0.3s ease;
    }
    
    .login-btn:hover {
        background: white !important;
        color: #667eea !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(255, 255, 255, 0.3);
    }
    
    .theme-toggle-btn {
        background: rgba(255, 255, 255, 0.2) !important;
        border: 2px solid rgba(255, 255, 255, 0.4) !important;
        border-radius: 50% !important;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        font-size: 1.2rem;
        padding: 0;
    }
    
    .theme-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.3) !important;
        transform: rotate(20deg) scale(1.1);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .navbar-toggler {
        border: 2px solid white !important;
        border-radius: 8px;
    }
    
    .navbar-toggler:focus {
        box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
    }
    
    @media (max-width: 991px) {
        .modern-navbar .nav-link {
            margin: 0.2rem 0;
        }
        
        .modern-navbar .navbar-nav {
            padding: 1rem 0;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark modern-navbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-graduation-cap"></i>RICA Database
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
                            <i class="fas fa-th-large"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'authors.php' ? 'active' : '' ?>" href="authors.php">
                            <i class="fas fa-user-graduate"></i> Authors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'papers.php' ? 'active' : '' ?>" href="papers.php">
                            <i class="fas fa-file-alt"></i> Papers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'journals.php' ? 'active' : '' ?>" href="journals.php">
                            <i class="fas fa-book-open"></i> Journals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'institutions.php' ? 'active' : '' ?>" href="institutions.php">
                            <i class="fas fa-university"></i> Institutions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'conferences.php' ? 'active' : '' ?>" href="conferences.php">
                            <i class="fas fa-users"></i> Conferences
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                            <i class="fas fa-chart-line"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'admin.php' ? 'active' : '' ?>" href="admin.php">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                    </li>
                    <li class="nav-item ms-1">
                        <span class="nav-link user-badge">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['admin_username']); ?>
                        </span>
                    </li>
                    <li class="nav-item ms-1">
                        <a class="nav-link logout-btn" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="nav-link login-btn" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Theme toggle button -->
                <li class="nav-item ms-2">
                    <button id="theme-toggle" class="btn theme-toggle-btn" title="Toggle Theme">
                        <span id="theme-icon">🌙</span>
                    </button>
                </li>

            </ul>
        </div>
    </div>
</nav>

<!-- Include the theme toggle JS -->
<script src="js/theme.js"></script>
