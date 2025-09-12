<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">Authors Management</h1>
        
        <!-- Add Author Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Add New Author</h5>
            </div>
            <div class="card-body">
                <form id="authorForm">
                    <input type="hidden" id="authorId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Field of Study</label>
                                <input type="text" class="form-control" id="field_of_study">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Institution</label>
                                <select class="form-select" id="institution_id">
                                    <option value="">Select Institution</option>
                                    <?php
                                    $institutions = $conn->query("SELECT * FROM Institutions");
                                    while ($inst = $institutions->fetch_assoc()) {
                                        echo "<option value='{$inst['institution_id']}'>{$inst['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Author</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Cancel</button>
                </form>
            </div>
        </div>

        <!-- Authors Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Authors List</h5>
                <button class="btn btn-success" onclick="exportData('Authors')">Export CSV</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Field of Study</th>
                                <th>Institution</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="authorsTable">
                            <!-- Data will be loaded via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load authors data
        async function loadAuthors() {
    try {
        const response = await fetch('api/authors.php');
        const result = await response.json();
        const authors = result.data || []; // <-- extract data array
        const table = document.getElementById('authorsTable');

        if (authors.length === 0) {
            table.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No authors found</td></tr>';
            return;
        }

        table.innerHTML = authors.map(author => `
            <tr>
                <td>${author.name}</td>
                <td>${author.email}</td>
                <td>${author.field_of_study || '-'}</td>
                <td>${author.institution_name || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editAuthor(${author.author_id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteAuthor(${author.author_id})">Delete</button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading authors:', error);
        document.getElementById('authorsTable').innerHTML =
            '<tr><td colspan="5" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
    }
}


        // Add/edit author
        document.getElementById('authorForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                field_of_study: document.getElementById('field_of_study').value,
                institution_id: document.getElementById('institution_id').value || null
            };

            const authorId = document.getElementById('authorId').value;
            const url = `api/authors.php${authorId ? '' : ''}`;
            const method = authorId ? 'PUT' : 'POST';

            if (authorId) formData.author_id = authorId;

            const response = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                loadAuthors();
                resetForm();
            }
        });

        // Edit author
        async function editAuthor(id) {
            const response = await fetch(`api/authors.php?id=${id}`);
            const author = await response.json();
            
            document.getElementById('authorId').value = author.author_id;
            document.getElementById('name').value = author.name;
            document.getElementById('email').value = author.email;
            document.getElementById('field_of_study').value = author.field_of_study || '';
            document.getElementById('institution_id').value = author.institution_id || '';
        }

        // Delete author
        async function deleteAuthor(id) {
            if (confirm('Are you sure you want to delete this author?')) {
                const response = await fetch(`api/authors.php`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                });
                if (response.ok) loadAuthors();
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('authorForm').reset();
            document.getElementById('authorId').value = '';
        }

        // Export data
        function exportData(table) {
            window.open(`api/export.php?table=${table}`, '_blank');
        }

        // Load data on page load
        loadAuthors();
    </script>
</body>
</html>
<?php $conn->close(); ?>