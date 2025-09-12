<?php
require_once 'config.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tools - Research Paper Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">
            <i class="fas fa-cog me-2"></i>Admin Tools
        </h1>

        <!-- Database Operations -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-database me-2"></i>Database Operations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-download fa-2x text-primary mb-2"></i>
                                        <h6>Export Database</h6>
                                        <button class="btn btn-outline-primary btn-sm" onclick="exportDatabase()">
                                            Export SQL
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-trash fa-2x text-warning mb-2"></i>
                                        <h6>Clear Test Data</h6>
                                        <button class="btn btn-outline-warning btn-sm" onclick="clearTestData()">
                                            Clear Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-sync fa-2x text-info mb-2"></i>
                                        <h6>Reset Counters</h6>
                                        <button class="btn btn-outline-info btn-sm" onclick="resetCounters()">
                                            Reset Auto-increment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SQL Query Interface -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-terminal me-2"></i>SQL Query Interface</h5>
                    </div>
                    <div class="card-body">
                        <form id="sqlForm">
                            <div class="mb-3">
                                <label class="form-label">SQL Query</label>
                                <textarea class="form-control" id="sqlQuery" rows="4" placeholder="SELECT * FROM Authors WHERE institution_id = 1" required></textarea>
                                <div class="form-text">Only SELECT queries are allowed for security</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-play me-1"></i>Execute Query
                            </button>
                        </form>
                        
                        <div id="queryResults" class="mt-3" style="display: none;">
                            <h6>Results:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped" id="resultsTable">
                                    <!-- Results will be populated here -->
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Statistics -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Database Statistics</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $tables = ['Authors', 'Papers', 'Journals', 'Institutions', 'Conferences', 'Citations'];
                        foreach ($tables as $table) {
                            $result = $conn->query("SELECT COUNT(*) as count FROM $table");
                            $count = $result->fetch_assoc()['count'];
                            echo "<div class='d-flex justify-content-between mb-2'>
                                    <span>$table</span>
                                    <span class='badge bg-primary'>$count</span>
                                  </div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-server me-2"></i>System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>PHP Version:</strong> <?php echo phpversion(); ?>
                        </div>
                        <div class="mb-2">
                            <strong>MySQL Version:</strong> <?php echo $conn->server_info; ?>
                        </div>
                        <div class="mb-2">
                            <strong>Database Size:</strong> 
                            <?php
                            $result = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size 
                                                   FROM information_schema.tables 
                                                   WHERE table_schema = '" . DB_NAME . "'");
                            echo $result->fetch_assoc()['size'] . ' MB';
                            ?>
                        </div>
                        <div class="mb-2">
                            <strong>Uptime:</strong> 
                            <?php
                            $result = $conn->query("SHOW STATUS LIKE 'Uptime'");
                            $uptime = $result->fetch_assoc()['Value'];
                            echo gmdate("H:i:s", $uptime);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Execute SQL query
        document.getElementById('sqlForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const query = document.getElementById('sqlQuery').value.trim();
            
            // Basic security check - only allow SELECT queries
            if (!query.toLowerCase().startsWith('select')) {
                alert('Only SELECT queries are allowed for security reasons.');
                return;
            }

            try {
                const response = await fetch('api/admin.php?action=query', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ query: query })
                });

                const result = await response.json();
                
                if (result.success) {
                    displayResults(result.data);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error executing query');
            }
        });

        function displayResults(data) {
            const resultsDiv = document.getElementById('queryResults');
            const table = document.getElementById('resultsTable');
            
            if (data.length === 0) {
                table.innerHTML = '<tr><td colspan="100" class="text-center">No results found</td></tr>';
                resultsDiv.style.display = 'block';
                return;
            }

            // Create table headers
            let headers = '<tr>';
            for (const key in data[0]) {
                headers += `<th>${key}</th>`;
            }
            headers += '</tr>';

            // Create table rows
            let rows = '';
            data.forEach(row => {
                rows += '<tr>';
                for (const key in row) {
                    rows += `<td>${row[key] || 'NULL'}</td>`;
                }
                rows += '</tr>';
            });

            table.innerHTML = headers + rows;
            resultsDiv.style.display = 'block';
        }

        function exportDatabase() {
            if (confirm('This will export the entire database as SQL. Continue?')) {
                window.open('api/export.php?type=full', '_blank');
            }
        }

        function clearTestData() {
            if (confirm('WARNING: This will delete all data except essential records. Continue?')) {
                // Implement via API call
                alert('This feature would be implemented via an API endpoint');
            }
        }

        function resetCounters() {
            if (confirm('Reset auto-increment counters? This does not delete data.')) {
                // Implement via API call
                alert('This feature would be implemented via an API endpoint');
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>