<?php
require_once 'config.php';

// Redirect to dashboard if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Paper Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-database me-2"></i>Research Paper DB
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>Admin Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Research Paper & Citation Database</h1>
                    <p class="lead mb-4">A comprehensive platform for managing academic research papers, authors, institutions, and citations with advanced analytics and reporting capabilities.</p>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Admin Portal
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1559028012-481c04fa702d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Research Papers" class="img-fluid rounded-3 shadow-lg" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Features</h2>
                <p class="lead text-muted">Comprehensive research database management system</p>
            </div>
            
            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title">Author Management</h4>
                            <p class="card-text">Complete CRUD operations for authors with institution associations and publication tracking.</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-file-alt fa-3x text-success"></i>
                            </div>
                            <h4 class="card-title">Paper Management</h4>
                            <p class="card-text">Manage research papers with journal/conference associations, abstracts, and publication details.</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-link fa-3x text-info"></i>
                            </div>
                            <h4 class="card-title">Citation Tracking</h4>
                            <p class="card-text">Track citations between papers with automatic citation counts and relationship mapping.</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-chart-bar fa-3x text-warning"></i>
                            </div>
                            <h4 class="card-title">Advanced Analytics</h4>
                            <p class="card-text">Comprehensive analytics including top-cited papers, author statistics, and publication trends.</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-download fa-3x text-danger"></i>
                            </div>
                            <h4 class="card-title">Data Export</h4>
                            <p class="card-text">Export data in CSV format for individual tables or complete database backup.</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-3">
                                <i class="fas fa-shield-alt fa-3x text-secondary"></i>
                            </div>
                            <h4 class="card-title">Secure Admin Panel</h4>
                            <p class="card-text">Role-based authentication with secure session management for administrative operations.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Database Stats -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Database Statistics</h2>
            </div>
            
            <div class="row">
                <?php
                $stats = [
                    'Authors' => ['SELECT COUNT(*) FROM Authors', 'users', 'primary'],
                    'Papers' => ['SELECT COUNT(*) FROM Papers', 'file-alt', 'success'],
                    'Journals' => ['SELECT COUNT(*) FROM Journals', 'book', 'info'],
                    'Institutions' => ['SELECT COUNT(*) FROM Institutions', 'building', 'warning'],
                    'Conferences' => ['SELECT COUNT(*) FROM Conferences', 'calendar-alt', 'danger'],
                    'Citations' => ['SELECT COUNT(*) FROM Citations', 'link', 'secondary']
                ];
                
                foreach ($stats as $title => [$query, $icon, $color]) {
                    $result = $conn->query($query);
                    $count = $result->fetch_row()[0];
                    echo "
                    <div class='col-md-4 col-lg-2 mb-3'>
                        <div class='card text-center border-$color'>
                            <div class='card-body'>
                                <i class='fas fa-$icon fa-2x text-$color mb-2'></i>
                                <h6 class='card-title'>$title</h6>
                                <h4 class='text-$color'>$count</h4>
                            </div>
                        </div>
                    </div>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Research Paper Database</h5>
                    <p class="text-muted">Comprehensive academic research management system</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted">© 2024 Research Paper DB. All rights reserved.</p>
                    <a href="login.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-in-alt me-1"></i>Admin Login
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>