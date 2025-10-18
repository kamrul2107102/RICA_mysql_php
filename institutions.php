<?php
require_once 'config.php';
require_once __DIR__ . '/includes/sql.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institutions Management - Research Paper Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

   

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-building me-2"></i>Institutions Management
            </h1>
            <button class="btn btn-primary" onclick="document.getElementById('institutionForm').scrollIntoView({ behavior: 'smooth' })">
                <i class="fas fa-plus"></i> Add New Institution
            </button>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <i class="fas fa-university fa-2x mb-2"></i>
                        <h5>Total Institutions</h5>
                        <?php
                        $total = $conn->query(sql_named('institutionQuery.sql', 'COUNT_TOTAL'))->fetch_assoc()['count'];
                        echo "<h3>$total</h3>";
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <i class="fas fa-globe fa-2x mb-2"></i>
                        <h5>Countries</h5>
                        <?php
                        $countries = $conn->query(sql_named('institutionQuery.sql', 'COUNT_COUNTRIES'))->fetch_assoc()['count'];
                        echo "<h3>$countries</h3>";
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h5>Authors</h5>
                        <?php
                        $authors = $conn->query("SELECT COUNT(*) as count FROM Authors WHERE institution_id IS NOT NULL")->fetch_assoc()['count'];
                        echo "<h3>$authors</h3>";
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Institution Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    <?php echo isset($_GET['edit']) ? 'Edit Institution' : 'Add New Institution'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form id="institutionForm">
                    <input type="hidden" id="institutionId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" id="name" required>
                                <div class="form-text">Institution name must be unique</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Country *</label>
                                <input type="text" class="form-control" id="country" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ranking</label>
                                <input type="number" class="form-control" id="ranking" min="1" placeholder="Enter ranking (1 = best)">
                                <div class="form-text">Lower number indicates better ranking. Leave empty if unknown.</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            <?php echo isset($_GET['edit']) ? 'Update Institution' : 'Save Institution'; ?>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <?php if (isset($_GET['edit'])): ?>
                        <a href="institutions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-plus me-1"></i>Add New
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Institutions Table -->
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Institutions List
                </h5>
                <div>
                    <button class="btn btn-light btn-sm me-2" onclick="loadInstitutions()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="exportData('Institutions')">
                        <i class="fas fa-download me-1"></i>Export CSV
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Country</th>
                                <th>Ranking</th>
                                <th>Authors</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="institutionsTable">
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading institutions...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load institutions data
        async function loadInstitutions() {
    try {
        const response = await fetch('api/institutions.php');
        const result = await response.json();

        if (!result.data || result.data.length === 0) {
            document.getElementById('institutionsTable').innerHTML =
                '<tr><td colspan="5" class="text-center text-muted">No institutions found</td></tr>';
            return;
        }

        const institutions = result.data;
        const table = document.getElementById('institutionsTable');

        // Get author counts
        const authorResponse = await fetch('api/authors.php');
        const authorResult = await authorResponse.json();
        const authors = authorResult.data || [];

        const authorCounts = {};
        authors.forEach(author => {
            if (author.institution_id) {
                authorCounts[author.institution_id] = (authorCounts[author.institution_id] || 0) + 1;
            }
        });

        table.innerHTML = institutions.map(institution => `
            <tr>
                <td>
                    <strong>${institution.name}</strong>
                    ${institution.ranking ? `<br><small class="text-muted">Rank: #${institution.ranking}</small>` : ''}
                </td>
                <td><span class="badge bg-info">${institution.country}</span></td>
                <td>${institution.ranking ? `#${institution.ranking}` : '<span class="text-muted">N/A</span>'}</td>
                <td>
                    <span class="badge ${authorCounts[institution.institution_id] ? 'bg-success' : 'bg-secondary'}">
                        ${authorCounts[institution.institution_id] || 0} authors
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning" onclick="editInstitution(${institution.institution_id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-info" onclick="viewAuthors(${institution.institution_id})" title="View Authors">
                            <i class="fas fa-users"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteInstitution(${institution.institution_id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error('Error loading institutions:', error);
        document.getElementById('institutionsTable').innerHTML =
            '<tr><td colspan="5" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
    }
}


        // Add/edit institution
        document.getElementById('institutionForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('name').value.trim(),
                country: document.getElementById('country').value.trim(),
                ranking: document.getElementById('ranking').value ? parseInt(document.getElementById('ranking').value) : null
            };

            const institutionId = document.getElementById('institutionId').value;
            const url = 'api/institutions.php';
            const method = institutionId ? 'PUT' : 'POST';

            if (institutionId) {
                formData.institution_id = parseInt(institutionId);
            }

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    loadInstitutions();
                    resetForm();
                    showAlert('Institution saved successfully!', 'success');
                    
                    // Scroll to table
                    document.getElementById('institutionsTable').scrollIntoView({ behavior: 'smooth' });
                } else {
                    showAlert('Error saving institution: ' + (result.error || 'Unknown error'), 'danger');
                }
            } catch (error) {
                console.error('Error saving institution:', error);
                showAlert('Error saving institution. Please try again.', 'danger');
            }
        });

        // Edit institution
        async function editInstitution(id) {
            try {
                const response = await fetch(`api/institutions.php?id=${id}`);
                const institution = await response.json();
                
                if (!institution) {
                    showAlert('Institution not found', 'danger');
                    return;
                }
                
                document.getElementById('institutionId').value = institution.institution_id;
                document.getElementById('name').value = institution.name;
                document.getElementById('country').value = institution.country;
                document.getElementById('ranking').value = institution.ranking || '';

                // Scroll to form
                document.getElementById('institutionForm').scrollIntoView({ behavior: 'smooth' });
                
                showAlert('Editing institution: ' + institution.name, 'info');
            } catch (error) {
                console.error('Error loading institution:', error);
                showAlert('Error loading institution data', 'danger');
            }
        }

        // View authors for institution
        async function viewAuthors(institutionId) {
            try {
                const response = await fetch('api/authors.php');
                const authors = await response.json();
                const institutionAuthors = authors.filter(author => author.institution_id == institutionId);
                
                if (institutionAuthors.length === 0) {
                    showAlert('No authors found for this institution', 'info');
                    return;
                }
                
                const authorList = institutionAuthors.map(author => 
                    `• ${author.name} (${author.email})`
                ).join('\n');
                
                alert(`Authors at this institution:\n\n${authorList}`);
            } catch (error) {
                console.error('Error loading authors:', error);
                showAlert('Error loading authors', 'danger');
            }
        }

        // Delete institution
        async function deleteInstitution(id) {
            if (!confirm('Are you sure you want to delete this institution?\n\nAuthors associated with this institution will have their institution set to NULL.')) {
                return;
            }

            try {
                const response = await fetch(`api/institutions.php`, {
                    method: 'DELETE',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: `id=${id}`
                });

                const result = await response.json();

                if (result.success) {
                    loadInstitutions();
                    resetForm();
                    showAlert('Institution deleted successfully!', 'success');
                } else {
                    showAlert('Error deleting institution: ' + (result.error || 'Unknown error'), 'danger');
                }
            } catch (error) {
                console.error('Error deleting institution:', error);
                showAlert('Error deleting institution. Please try again.', 'danger');
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('institutionForm').reset();
            document.getElementById('institutionId').value = '';
        }

        // Export data
        function exportData(table) {
            window.open(`api/export.php?table=${table}`, '_blank');
        }

        // Show alert
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Remove alert after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Check if we need to edit an institution from URL parameter
        function checkEditFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            if (editId) {
                editInstitution(parseInt(editId));
            }
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadInstitutions();
            checkEditFromUrl();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>