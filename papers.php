<?php 
require_once 'config.php';
require_once __DIR__ . '/includes/sql.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papers Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Papers Management</h1>
            <button class="btn btn-primary" onclick="document.getElementById('paperForm').scrollIntoView({ behavior: 'smooth' })">
                <i class="fas fa-plus"></i> Add New Paper
            </button>
        </div>
        
        <!-- Add Paper Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Add New Paper</h5>
            </div>
            <div class="card-body">
                <form id="paperForm">
                    <input type="hidden" id="paperId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Publication Year</label>
                                <input type="number" class="form-control" id="publication_year" min="1900" max="2100" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Journal</label>
                                <select class="form-select" id="journal_id">
                                    <option value="">Select Journal</option>
                                    <?php
                                    $journals = $conn->query("SELECT journal_id, name FROM Journals ORDER BY name LIMIT 1000");
                                    while ($journal = $journals->fetch_assoc()) {
                                        echo "<option value='{$journal['journal_id']}'>{$journal['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Conference</label>
                                <select class="form-select" id="conference_id">
                                    <option value="">Select Conference</option>
                                    <?php
                                    $conferences = $conn->query("SELECT conference_id, name FROM Conferences ORDER BY name LIMIT 1000");
                                    while ($conf = $conferences->fetch_assoc()) {
                                        echo "<option value='{$conf['conference_id']}'>{$conf['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Abstract</label>
                        <textarea class="form-control" id="abstract" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Paper</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Cancel</button>
                </form>
            </div>
        </div>

        <!-- Papers Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Papers List</h5>
                <button class="btn btn-success" onclick="exportData('Papers')">Export CSV</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Year</th>
                                <th>Journal</th>
                                <th>Conference</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="papersTable">
                            <!-- Data will be loaded via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load papers data
        async function loadPapers() {
    try {
        const response = await fetch('api/papers.php');
        const result = await response.json();
        const papers = result.data || []; // extract the data array
        const table = document.getElementById('papersTable');

        if (papers.length === 0) {
            table.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No papers found</td></tr>';
            return;
        }

        table.innerHTML = papers.map(paper => `
            <tr>
                <td>${paper.title}</td>
                <td>${paper.publication_year}</td>
                <td>${paper.journal_name || '-'}</td>
                <td>${paper.conference_name || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editPaper(${paper.paper_id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deletePaper(${paper.paper_id})">Delete</button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading papers:', error);
        document.getElementById('papersTable').innerHTML =
            '<tr><td colspan="5" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
    }
}


        // Add/edit paper
        document.getElementById('paperForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                title: document.getElementById('title').value,
                publication_year: parseInt(document.getElementById('publication_year').value),
                abstract: document.getElementById('abstract').value || null,
                journal_id: document.getElementById('journal_id').value || null,
                conference_id: document.getElementById('conference_id').value || null
            };

            const paperId = document.getElementById('paperId').value;
            const url = 'api/papers.php';
            const method = paperId ? 'PUT' : 'POST';

            if (paperId) {
                formData.paper_id = parseInt(paperId);
            }

            console.log('Submitting form with method:', method);
            console.log('Form data:', formData);

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                console.log('Response status:', response.status);
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse JSON:', parseError);
                    console.error('Response was:', responseText);
                    showAlert('Error: Invalid response from server', 'danger');
                    return;
                }
                
                console.log('Parsed result:', result);

                if (result.success) {
                    loadPapers();
                    resetForm();
                    showAlert('Paper saved successfully!', 'success');
                } else {
                    const errorMsg = result.error || 'Unknown error';
                    console.error('API Error:', errorMsg, result);
                    showAlert('Error: ' + errorMsg, 'danger');
                }
            } catch (error) {
                console.error('Error saving paper:', error);
                showAlert('Error saving paper: ' + error.message, 'danger');
            }
        });

        // Edit paper
        async function editPaper(id) {
            try {
                const response = await fetch(`api/papers.php?id=${id}`);
                const paper = await response.json();
                
                document.getElementById('paperId').value = paper.paper_id;
                document.getElementById('title').value = paper.title;
                document.getElementById('publication_year').value = paper.publication_year;
                document.getElementById('abstract').value = paper.abstract || '';
                document.getElementById('journal_id').value = paper.journal_id || '';
                document.getElementById('conference_id').value = paper.conference_id || '';
                
                // Scroll to form
                document.getElementById('paperForm').scrollIntoView({ behavior: 'smooth' });
            } catch (error) {
                console.error('Error loading paper:', error);
                showAlert('Error loading paper data', 'danger');
            }
        }

        // Delete paper
        async function deletePaper(id) {
            if (confirm('Are you sure you want to delete this paper? This action cannot be undone.')) {
                try {
                    const response = await fetch(`api/papers.php`, {
                        method: 'DELETE',
                        headers: { 
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json'
                        },
                        body: `id=${id}`
                    });

                    const result = await response.json();

                    if (result.success) {
                        loadPapers();
                        showAlert('Paper deleted successfully!', 'success');
                    } else {
                        showAlert('Error deleting paper', 'danger');
                    }
                } catch (error) {
                    console.error('Error deleting paper:', error);
                    showAlert('Error deleting paper', 'danger');
                }
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('paperForm').reset();
            document.getElementById('paperId').value = '';
        }

        // Export data
        function exportData(table) {
            window.open(`api/export.php?table=${table}`, '_blank');
        }

        // Show alert
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Remove alert after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadPapers);
    </script>
</body>
</html>
<?php $conn->close(); ?>