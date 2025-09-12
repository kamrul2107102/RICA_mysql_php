<?php
require_once 'config.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Research Paper Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">
            <i class="fas fa-chart-bar me-2"></i>Analytics Dashboard
        </h1>

        <!-- Analytics Type Selector -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Analytics Type</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary active" onclick="loadAnalytics('overview')">
                                <i class="fas fa-home me-1"></i>Overview
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="loadAnalytics('top_cited')">
                                <i class="fas fa-star me-1"></i>Top Cited Papers
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="loadAnalytics('top_authors')">
                                <i class="fas fa-users me-1"></i>Top Authors
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="loadAnalytics('publication_stats')">
                                <i class="fas fa-calendar me-1"></i>Publication Stats
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="loadAnalytics('institution_stats')">
                                <i class="fas fa-building me-1"></i>Institution Stats
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="text-center mb-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading analytics data...</p>
        </div>

        <!-- Analytics Content -->
        <div id="analyticsContent">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentChart = null;

        // Load analytics data
        async function loadAnalytics(type = 'overview') {
            showLoading(true);
            
            try {
                const response = await fetch(`api/analytics.php?type=${type}`);
                const result = await response.json();
                
                if (result.success) {
                    renderAnalytics(type, result.data);
                } else {
                    showError(result.error);
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
                showError('Failed to load analytics data');
            } finally {
                showLoading(false);
            }
        }

        function renderAnalytics(type, data) {
            const contentDiv = document.getElementById('analyticsContent');
            
            switch (type) {
                case 'overview':
                    renderOverview(data, contentDiv);
                    break;
                case 'top_cited':
                    renderTopCited(data, contentDiv);
                    break;
                case 'top_authors':
                    renderTopAuthors(data, contentDiv);
                    break;
                case 'publication_stats':
                    renderPublicationStats(data, contentDiv);
                    break;
                case 'institution_stats':
                    renderInstitutionStats(data, contentDiv);
                    break;
            }
        }

        function renderOverview(data, container) {
            container.innerHTML = `
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                <h5>Total Papers</h5>
                                <h3>${data.total_papers}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h5>Total Authors</h5>
                                <h3>${data.total_authors}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <i class="fas fa-link fa-2x mb-2"></i>
                                <h5>Total Citations</h5>
                                <h3>${data.total_citations}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center bg-warning text-white">
                            <div class="card-body">
                                <i class="fas fa-book fa-2x mb-2"></i>
                                <h5>Journals</h5>
                                <h3>${data.total_journals}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center bg-danger text-white">
                            <div class="card-body">
                                <i class="fas fa-building fa-2x mb-2"></i>
                                <h5>Institutions</h5>
                                <h3>${data.total_institutions}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center bg-secondary text-white">
                            <div class="card-body">
                                <i class="fas fa-calendar fa-2x mb-2"></i>
                                <h5>Conferences</h5>
                                <h3>${data.total_conferences}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderTopCited(data, container) {
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No cited papers found</div>';
                return;
            }

            container.innerHTML = `
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>Top Cited Papers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Paper Title</th>
                                        <th>Year</th>
                                        <th>Citations</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map((paper, index) => `
                                        <tr>
                                            <td><strong>#${index + 1}</strong></td>
                                            <td>${paper.title}</td>
                                            <td>${paper.publication_year}</td>
                                            <td><span class="badge bg-primary">${paper.citation_count}</span></td>
                                            <td>${paper.journal_name || paper.conference_name || 'Other'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderTopAuthors(data, container) {
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No author data found</div>';
                return;
            }

            container.innerHTML = `
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Top Authors by Average Citations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Author</th>
                                        <th>Institution</th>
                                        <th>Papers</th>
                                        <th>Citations</th>
                                        <th>Avg Citations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map((author, index) => `
                                        <tr>
                                            <td><strong>#${index + 1}</strong></td>
                                            <td>${author.name} <br><small class="text-muted">${author.email}</small></td>
                                            <td>${author.institution_name || 'N/A'}</td>
                                            <td>${author.paper_count}</td>
                                            <td>${author.citation_count}</td>
                                            <td><span class="badge bg-success">${author.avg_citations}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderPublicationStats(data, container) {
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No publication data found</div>';
                return;
            }

            // Destroy previous chart if exists
            if (currentChart) {
                currentChart.destroy();
            }

            const years = data.map(item => item.publication_year);
            const paperCounts = data.map(item => item.paper_count);
            const journalCounts = data.map(item => item.journal_papers);
            const conferenceCounts = data.map(item => item.conference_papers);

            container.innerHTML = `
                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Publication Trends</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="publicationChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Publication Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Year</th>
                                                <th>Total Papers</th>
                                                <th>Journal Papers</th>
                                                <th>Conference Papers</th>
                                                <th>Other</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.map(item => `
                                                <tr>
                                                    <td><strong>${item.publication_year}</strong></td>
                                                    <td>${item.paper_count}</td>
                                                    <td>${item.journal_papers}</td>
                                                    <td>${item.conference_papers}</td>
                                                    <td>${item.paper_count - item.journal_papers - item.conference_papers}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Create chart
            const ctx = document.getElementById('publicationChart').getContext('2d');
            currentChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: years,
                    datasets: [
                        {
                            label: 'Total Papers',
                            data: paperCounts,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Journal Papers',
                            data: journalCounts,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            fill: true
                        },
                        {
                            label: 'Conference Papers',
                            data: conferenceCounts,
                            borderColor: '#ffc107',
                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Publication Trends Over Time'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Papers'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Year'
                            }
                        }
                    }
                }
            });
        }

        function renderInstitutionStats(data, container) {
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No institution data found</div>';
                return;
            }

            container.innerHTML = `
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Institution Rankings</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Institution</th>
                                        <th>Country</th>
                                        <th>Authors</th>
                                        <th>Papers</th>
                                        <th>Citations</th>
                                        <th>Ranking</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map((institution, index) => `
                                        <tr>
                                            <td><strong>#${index + 1}</strong></td>
                                            <td>${institution.name}</td>
                                            <td>${institution.country}</td>
                                            <td>${institution.author_count}</td>
                                            <td>${institution.paper_count}</td>
                                            <td><span class="badge bg-primary">${institution.citation_count}</span></td>
                                            <td>${institution.ranking ? '#' + institution.ranking : 'N/A'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        function showLoading(show) {
            document.getElementById('loadingSpinner').style.display = show ? 'block' : 'none';
        }

        function showError(message) {
            const contentDiv = document.getElementById('analyticsContent');
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
        }

        // Load overview by default
        document.addEventListener('DOMContentLoaded', function() {
            loadAnalytics('overview');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>