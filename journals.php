<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journals Management</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<body>
  
<?php include 'navbar.php'; ?>


    <div class="container mt-5">
        <h1 class="text-center mb-4">Journals Management</h1>
        
        <!-- Add Journal Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Add New Journal</h5>
            </div>
            <div class="card-body">
                <form id="journalForm">
                    <input type="hidden" id="journalId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" id="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Publisher</label>
                                <input type="text" class="form-control" id="publisher">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">ISSN</label>
                                <input type="text" class="form-control" id="ISSN" placeholder="e.g., 1234-5678">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Impact Factor</label>
                                <input type="number" class="form-control" id="impact_factor" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Journal</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Cancel</button>
                </form>
            </div>
        </div>

        <!-- Journals Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Journals List</h5>
                <button class="btn btn-success" onclick="exportData('Journals')">Export CSV</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Publisher</th>
                                <th>ISSN</th>
                                <th>Impact Factor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="journalsTable">
                            <!-- Data will be loaded via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load journals data
        async function loadJournals() {
    try {
        const response = await fetch('api/journals.php');
        const result = await response.json();

        if (!result.success) throw new Error('API returned error');

        const journals = result.data; // <-- use the `data` array
        const table = document.getElementById('journalsTable');

        if (journals.length === 0) {
            table.innerHTML = '<tr><td colspan="5" class="text-center">No journals found</td></tr>';
            return;
        }

        table.innerHTML = journals.map(journal => `
            <tr>
                <td>${journal.name}</td>
                <td>${journal.publisher || '-'}</td>
                <td>${journal.ISSN || '-'}</td>
                <td>${journal.impact_factor || '0.00'}</td>
                <td>
                    <button class="btn btn-sm btn-warning me-2" onclick="editJournal(${journal.journal_id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteJournal(${journal.journal_id})">Delete</button>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error('Error loading journals:', error);
        document.getElementById('journalsTable').innerHTML =
            '<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>';
    }
}


        // Add/edit journal
        document.getElementById('journalForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('name').value,
                publisher: document.getElementById('publisher').value || null,
                ISSN: document.getElementById('ISSN').value || null,
                impact_factor: document.getElementById('impact_factor').value || 0.0
            };

            const journalId = document.getElementById('journalId').value;
            const url = 'api/journals.php';
            const method = journalId ? 'PUT' : 'POST';

            if (journalId) {
                formData.journal_id = parseInt(journalId);
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
                    loadJournals();
                    resetForm();
                    showAlert('Journal saved successfully!', 'success');
                } else {
                    showAlert('Error saving journal', 'danger');
                }
            } catch (error) {
                console.error('Error saving journal:', error);
                showAlert('Error saving journal', 'danger');
            }
        });

        // Edit journal
        async function editJournal(id) {
            try {
                const response = await fetch(`api/journals.php?id=${id}`);
                const journal = await response.json();
                
                document.getElementById('journalId').value = journal.journal_id;
                document.getElementById('name').value = journal.name;
                document.getElementById('publisher').value = journal.publisher || '';
                document.getElementById('ISSN').value = journal.ISSN || '';
                document.getElementById('impact_factor').value = journal.impact_factor || '';

                // Scroll to form
                document.getElementById('journalForm').scrollIntoView({ behavior: 'smooth' });
            } catch (error) {
                console.error('Error loading journal:', error);
                showAlert('Error loading journal data', 'danger');
            }
        }

        // Delete journal
        async function deleteJournal(id) {
            if (confirm('Are you sure you want to delete this journal? This action cannot be undone.')) {
                try {
                    const response = await fetch(`api/journals.php`, {
                        method: 'DELETE',
                        headers: { 
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json'
                        },
                        body: `id=${id}`
                    });

                    const result = await response.json();

                    if (result.success) {
                        loadJournals();
                        showAlert('Journal deleted successfully!', 'success');
                    } else {
                        showAlert('Error deleting journal', 'danger');
                    }
                } catch (error) {
                    console.error('Error deleting journal:', error);
                    showAlert('Error deleting journal', 'danger');
                }
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('journalForm').reset();
            document.getElementById('journalId').value = '';
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
        document.addEventListener('DOMContentLoaded', loadJournals);
    </script>
</body>
</html>
<?php $conn->close(); ?>