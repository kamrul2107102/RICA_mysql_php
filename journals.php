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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Journals Management</h1>
            <button class="btn btn-primary" onclick="document.getElementById('journalForm').scrollIntoView({ behavior: 'smooth' })">
                <i class="fas fa-plus"></i> Add New Journal
            </button>
        </div>
        
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
                            <div class="mb-3">
                                <label class="form-label">ISSN (Print)</label>
                                <input type="text" class="form-control" id="ISSN" placeholder="e.g., 1234-5678">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-ISSN (Electronic)</label>
                                <input type="text" class="form-control" id="eissn" placeholder="e.g., 1234-5679">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Impact Factor</label>
                                <input type="number" class="form-control" id="impact_factor" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Website URL</label>
                                <input type="url" class="form-control" id="website" placeholder="https://example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" placeholder="e.g., USA, UK, Germany">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Open Access Status</label>
                                <select class="form-select" id="open_access">
                                    <option value="No">No (Subscription-based)</option>
                                    <option value="Yes">Yes (Open Access)</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Scope / Description</label>
                                <textarea class="form-control" id="scope" rows="3" placeholder="Brief description of journal's research areas and topics"></textarea>
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
                                <th>ISSN / E-ISSN</th>
                                <th>Impact Factor</th>
                                <th>Open Access</th>
                                <th>Country</th>
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
            table.innerHTML = '<tr><td colspan="7" class="text-center">No journals found</td></tr>';
            return;
        }

        table.innerHTML = journals.map(journal => {
            // Combine ISSN and E-ISSN for display
            let issnDisplay = [];
            if (journal.ISSN) issnDisplay.push(journal.ISSN);
            if (journal.eissn) issnDisplay.push(`E: ${journal.eissn}`);
            const issnText = issnDisplay.length > 0 ? issnDisplay.join('<br>') : '-';
            
            // Open Access badge
            let accessBadge = '';
            if (journal.open_access === 'Yes') {
                accessBadge = '<span class="badge bg-success">Open</span>';
            } else if (journal.open_access === 'Hybrid') {
                accessBadge = '<span class="badge bg-warning">Hybrid</span>';
            } else {
                accessBadge = '<span class="badge bg-secondary">Subscription</span>';
            }
            
            return `
                <tr>
                    <td>
                        <strong>${journal.name}</strong>
                        ${journal.website ? `<br><a href="${journal.website}" target="_blank" class="text-muted small"><i class="fas fa-external-link-alt"></i> Website</a>` : ''}
                    </td>
                    <td>${journal.publisher || '-'}</td>
                    <td>${issnText}</td>
                    <td>${journal.impact_factor || '0.00'}</td>
                    <td>${accessBadge}</td>
                    <td>${journal.country || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-warning me-1" onclick="editJournal(${journal.journal_id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteJournal(${journal.journal_id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

    } catch (error) {
        console.error('Error loading journals:', error);
        document.getElementById('journalsTable').innerHTML =
            '<tr><td colspan="7" class="text-center text-danger">Error loading data</td></tr>';
    }
}


        // Add/edit journal
        document.getElementById('journalForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('name').value,
                publisher: document.getElementById('publisher').value || null,
                ISSN: document.getElementById('ISSN').value || null,
                eissn: document.getElementById('eissn').value || null,
                impact_factor: document.getElementById('impact_factor').value || 0.0,
                website: document.getElementById('website').value || null,
                scope: document.getElementById('scope').value || null,
                open_access: document.getElementById('open_access').value || 'No',
                country: document.getElementById('country').value || null
            };

            const journalId = document.getElementById('journalId').value;
            const url = 'api/journals.php';
            const method = journalId ? 'PUT' : 'POST';

            if (journalId) {
                formData.journal_id = parseInt(journalId);
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
                console.log('Response headers:', response.headers.get('content-type'));
                
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
                    loadJournals();
                    resetForm();
                    showAlert('Journal saved successfully!', 'success');
                } else {
                    const errorMsg = result.error || 'Unknown error';
                    console.error('API Error:', errorMsg, result);
                    showAlert('Error: ' + errorMsg, 'danger');
                }
            } catch (error) {
                console.error('Error saving journal:', error);
                showAlert('Error saving journal: ' + error.message, 'danger');
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
                document.getElementById('eissn').value = journal.eissn || '';
                document.getElementById('impact_factor').value = journal.impact_factor || '';
                document.getElementById('website').value = journal.website || '';
                document.getElementById('scope').value = journal.scope || '';
                document.getElementById('open_access').value = journal.open_access || 'No';
                document.getElementById('country').value = journal.country || '';

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