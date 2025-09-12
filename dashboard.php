<?php
require_once 'config.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Research Paper Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="text-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                    </h1>
                    <span class="badge bg-success">
                        <i class="fas fa-user me-1"></i>Welcome, <?php echo $_SESSION['admin_username']; ?>
                    </span>
                </div>
                <p class="text-muted">Manage your research paper database efficiently</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
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
                            <h5 class='card-title'>$title</h5>
                            <h3 class='text-$color'>$count</h3>
                        </div>
                    </div>
                </div>";
            }
            ?>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <a href="authors.php?action=create" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-plus me-1"></i>Add Author
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="papers.php?action=create" class="btn btn-outline-success w-100">
                                    <i class="fas fa-plus me-1"></i>Add Paper
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="journals.php?action=create" class="btn btn-outline-info w-100">
                                    <i class="fas fa-plus me-1"></i>Add Journal
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="analytics.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-chart-bar me-1"></i>View Analytics
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="admin.php" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-cog me-1"></i>Admin Tools
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="logout.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <!-- Recent Papers -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Papers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Year</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT p.title, p.publication_year, 
                                             CASE 
                                                 WHEN p.journal_id IS NOT NULL THEN 'Journal'
                                                 WHEN p.conference_id IS NOT NULL THEN 'Conference'
                                                 ELSE 'Other'
                                             END as type
                                             FROM Papers p
                                             ORDER BY p.publication_year DESC, p.paper_id DESC 
                                             LIMIT 5";
                                    $result = $conn->query($query);
                                    
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td><small>" . substr($row['title'], 0, 30) . "...</small></td>
                                                <td>{$row['publication_year']}</td>
                                                <td><span class='badge bg-info'>{$row['type']}</span></td>
                                              </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>IP Address:</strong> <?php echo $_SESSION['ip_address']; ?>
                        </div>
                        <div class="mb-3">
                            <strong>Session Duration:</strong> 
                            <?php 
                            $duration = time() - $_SESSION['login_time'];
                            echo gmdate("H:i:s", $duration);
                            ?>
                        </div>
                        <div class="mb-3">
                            <strong>Database:</strong> <?php echo DB_NAME; ?>
                        </div>
                        <div>
                            <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh session duration every minute
        setInterval(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
<?php $conn->close(); ?>