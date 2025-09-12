<?php
require_once 'config.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferences Management - Research Paper Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

    
    <div class="container mt-5">
        <h1 class="text-center mb-4">
            <i class="fas fa-calendar-alt me-2"></i>Conferences Management
        </h1>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <h5>Total Conferences</h5>
                        <?php
                        $total = $conn->query("SELECT COUNT(*) as count FROM Conferences")->fetch_assoc()['count'];
                        echo "<h3>$total</h3>";
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                        <h5>Locations</h5>
                        <?php
                        $locations = $conn->query("SELECT COUNT(DISTINCT location) as count FROM Conferences WHERE location IS NOT NULL")->fetch_assoc()['count'];
                        echo "<h3>$locations</h3>";
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <i class="fas fa-file-alt fa-2x mb-2"></i>
                        <h5>Conference Papers</h5>
                        <?php
                        $papers = $conn->query("SELECT COUNT(*) as count FROM Papers WHERE conference_id IS NOT NULL")->fetch_assoc()['count'];
                        echo "<h3>$papers</h3>";
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Conference Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    <?php echo isset($_GET['edit']) ? 'Edit Conference' : 'Add New Conference'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form id="conferenceForm">
                    <input type="hidden" id="conferenceId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" id="name" required>
                                <div class="form-text">Conference name (e.g., ICML, NeurIPS)</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" placeholder="e.g., San Francisco, USA">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Year *</label>
                                <input type="number" class="form-control" id="year" min="1900" max="2100" required>
                                <div class="form-text">Year the conference was held</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Organizer</label>
                                <input type="text" class="form-control" id="organizer" placeholder="e.g., IEEE, ACM">
                                <div class="form-text">Default: 'Unknown'</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            <?php echo isset($_GET['edit']) ? 'Update Conference' : 'Save Conference'; ?>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <?php if (isset($_GET['edit'])): ?>
                        <a href="conferences.php" class="btn btn-outline-secondary">
                            <i class="fas fa-plus me-1"></i>Add New
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Conferences Table -->
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Conferences List
                </h5>
                <div>
                    <button class="btn btn-light btn-sm me-2" onclick="loadConferences()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="exportData('Conferences')">
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
                                <th>Location</th>
                                <th>Year</th>
                                <th>Organizer</th>
                                <th>Papers</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="conferencesTable">
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading conferences...</p>
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
        // Load conferences data
        async function loadConferences() {
    try {
        const response = await fetch('api/conferences.php');
        const result = await response.json();
        const conferences = result.data || [];
        const table = document.getElementById('conferencesTable');

        if (conferences.length === 0) {
            table.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No conferences found</td></tr>';
            return;
        }

        // Get paper counts for each conference
        const papersResponse = await fetch('api/papers.php');
        const papersResult = await papersResponse.json();
        const papers = papersResult.data || [];

        const paperCounts = {};
        papers.forEach(paper => {
            if (paper.conference_id) {
                paperCounts[paper.conference_id] = (paperCounts[paper.conference_id] || 0) + 1;
            }
        });

        table.innerHTML = conferences.map(conference => `
            <tr>
                <td><strong>${conference.name}</strong></td>
                <td>${conference.location ? `<span class="badge bg-info">${conference.location}</span>` : '<span class="text-muted">N/A</span>'}</td>
                <td><span class="badge bg-primary">${conference.year}</span></td>
                <td>${conference.organizer || '<span class="text-muted">Unknown</span>'}</td>
                <td>
                    <span class="badge ${paperCounts[conference.conference_id] ? 'bg-success' : 'bg-secondary'}">
                        ${paperCounts[conference.conference_id] || 0} papers
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning" onclick="editConference(${conference.conference_id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-info" onclick="viewPapers(${conference.conference_id})" title="View Papers">
                            <i class="fas fa-file-alt"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteConference(${conference.conference_id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error('Error loading conferences:', error);
        document.getElementById('conferencesTable').innerHTML =
            '<tr><td colspan="6" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
    }
}


        // Add/edit conference
        document.getElementById('conferenceForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('name').value.trim(),
                location: document.getElementById('location').value.trim() || null,
                year: parseInt(document.getElementById('year').value),
                organizer: document.getElementById('organizer').value.trim() || 'Unknown'
            };

            const conferenceId = document.getElementById('conferenceId').value;
            const url = 'api/conferences.php';
            const method = conferenceId ? 'PUT' : 'POST';

            if (conferenceId) {
                formData.conference_id = parseInt(conferenceId);
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
                    loadConferences();
                    resetForm();
                    showAlert('Conference saved successfully!', 'success');
                    
                    // Scroll to table
                    document.getElementById('conferencesTable').scrollIntoView({ behavior: 'smooth' });
                } else {
                    showAlert('Error saving conference: ' + (result.error || 'Unknown error'), 'danger');
                }
            } catch (error) {
                console.error('Error saving conference:', error);
                showAlert('Error saving conference. Please try again.', 'danger');
            }
        });

        // Edit conference
        async function editConference(id) {
            try {
                const response = await fetch(`api/conferences.php?id=${id}`);
                const conference = await response.json();
                
                if (!conference) {
                    showAlert('Conference not found', 'danger');
                    return;
                }
                
                document.getElementById('conferenceId').value = conference.conference_id;
                document.getElementById('name').value = conference.name;
                document.getElementById('location').value = conference.location || '';
                document.getElementById('year').value = conference.year;
                document.getElementById('organizer').value = conference.organizer || '';

                // Scroll to form
                document.getElementById('conferenceForm').scrollIntoView({ behavior: 'smooth' });
                
                showAlert('Editing conference: ' + conference.name, 'info');
            } catch (error) {
                console.error('Error loading conference:', error);
                showAlert('Error loading conference data', 'danger');
            }
        }

        // View papers for conference
        async function viewPapers(conferenceId) {
            try {
                const response = await fetch('api/papers.php');
                const papers = await response.json();
                const conferencePapers = papers.filter(paper => paper.conference_id == conferenceId);
                
                if (conferencePapers.length === 0) {
                    showAlert('No papers found for this conference', 'info');
                    return;
                }
                
                const paperList = conferencePapers.map(paper => 
                    `• ${paper.title} (${paper.publication_year})`
                ).join('\n');
                
                alert(`Papers from this conference:\n\n${paperList}`);
            } catch (error) {
                console.error('Error loading papers:', error);
                showAlert('Error loading papers', 'danger');
            }
        }

        // Delete conference
        async function deleteConference(id) {
            if (!confirm('Are you sure you want to delete this conference?\n\nPapers associated with this conference will have their conference set to NULL.')) {
                return;
            }

            try {
                const response = await fetch(`api/conferences.php`, {
                    method: 'DELETE',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: `id=${id}`
                });

                const result = await response.json();

                if (result.success) {
                    loadConferences();
                    resetForm();
                    showAlert('Conference deleted successfully!', 'success');
                } else {
                    showAlert('Error deleting conference: ' + (result.error || 'Unknown error'), 'danger');
                }
            } catch (error) {
                console.error('Error deleting conference:', error);
                showAlert('Error deleting conference. Please try again.', 'danger');
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('conferenceForm').reset();
            document.getElementById('conferenceId').value = '';
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

        // Check if we need to edit a conference from URL parameter
        function checkEditFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            if (editId) {
                editConference(parseInt(editId));
            }
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadConferences();
            checkEditFromUrl();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>