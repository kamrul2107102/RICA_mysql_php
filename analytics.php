<?php
require_once 'config.php';
// Temporarily disabled for testing
// require_login();
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
    <style>
        .analytics-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .stats-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stats-card .card-body {
            padding: 2rem;
        }
        
        .stats-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            background: rgba(255,255,255,0.2);
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stats-card h5 {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }
        
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .gradient-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .gradient-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .gradient-secondary {
            background: linear-gradient(135deg, #fd7e14 0%, #ff6b00 100%);
        }
        
        .analytics-tabs {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .analytics-tabs .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .analytics-tabs .btn-outline-primary {
            color: #667eea;
            border-color: #e0e0e0;
        }
        
        .analytics-tabs .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .analytics-tabs .btn-outline-primary.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .data-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .data-card .card-header {
            padding: 1.5rem;
            font-weight: 600;
            border-bottom: 2px solid rgba(255,255,255,0.3);
        }
        
        .table-modern {
            margin: 0;
        }
        
        .table-modern thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            color: #495057;
        }
        
        .table-modern tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table-modern tbody tr:hover {
            background: #f8f9ff;
            transform: scale(1.01);
        }
        
        .table-modern tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .badge-modern {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .rank-gold {
            background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
            color: white;
        }
        
        .rank-silver {
            background: linear-gradient(135deg, #bdc3c7 0%, #ecf0f1 100%);
            color: #34495e;
        }
        
        .rank-bronze {
            background: linear-gradient(135deg, #cd7f32 0%, #e8a06c 100%);
            color: white;
        }
        
        .rank-default {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .loading-spinner {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .spinner-border {
            width: 4rem;
            height: 4rem;
            border-width: 0.4rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4 mb-5">
        <!-- Hero Section -->
        <div class="analytics-hero text-center fade-in">
            <h1 class="display-4 mb-3">
                <i class="fas fa-chart-line me-3"></i>Analytics Dashboard
            </h1>
            <p class="lead mb-0">Comprehensive insights into your research database</p>
        </div>

        <!-- Analytics Type Selector -->
        <div class="analytics-tabs fade-in">
            <div class="row g-2">
                <div class="col-12">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="loadAnalytics('overview')">
                            <i class="fas fa-home me-2"></i>Overview
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadAnalytics('top_cited')">
                            <i class="fas fa-star me-2"></i>Top Cited
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadAnalytics('top_authors')">
                            <i class="fas fa-user-graduate me-2"></i>Top Authors
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadAnalytics('publication_stats')">
                            <i class="fas fa-chart-bar me-2"></i>Publications
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="loadAnalytics('institution_stats')">
                            <i class="fas fa-university me-2"></i>Institutions
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="loading-spinner text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted fw-semibold">Loading analytics data...</p>
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
            
            // Update active button
            document.querySelectorAll('.analytics-tabs .btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('onclick').includes(type)) {
                    btn.classList.add('active');
                }
            });
            
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
                showError('Failed to load analytics data. Please try again.');
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
                <div class="row g-4 mb-4 fade-in">
                    <div class="col-md-4">
                        <div class="stats-card card gradient-primary text-white">
                            <div class="card-body text-center">
                                <div class="stats-icon">
                                    <i class="fas fa-file-alt fa-2x"></i>
                                </div>
                                <h5>Total Papers</h5>
                                <h3 class="mb-0">${data.total_papers.toLocaleString()}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card card gradient-success text-white">
                            <div class="card-body text-center">
                                <div class="stats-icon">
                                    <i class="fas fa-user-graduate fa-2x"></i>
                                </div>
                                <h5>Total Authors</h5>
                                <h3 class="mb-0">${data.total_authors.toLocaleString()}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card card gradient-info text-white">
                            <div class="card-body text-center">
                                <div class="stats-icon">
                                    <i class="fas fa-quote-right fa-2x"></i>
                                </div>
                                <h5>Total Citations</h5>
                                <h3 class="mb-0">${data.total_citations.toLocaleString()}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-4 fade-in">
                    <div class="col-md-4">
                        <div class="stats-card card gradient-warning text-white">
                            <div class="card-body text-center">
                                <div class="stats-icon">
                                    <i class="fas fa-book-open fa-2x"></i>
                                </div>
                                <h5>Journals</h5>
                                <h3 class="mb-0">${data.total_journals.toLocaleString()}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card card gradient-danger text-white">
                            <div class="card-body text-center">
                                <div class="stats-icon">
                                    <i class="fas fa-university fa-2x"></i>
                                </div>
                                <h5>Institutions</h5>
                                <h3 class="mb-0">${data.total_institutions.toLocaleString()}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card card gradient-secondary text-white">
                            <div class="card-body text-center">
                                <div class="stats-icon">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                                <h5>Conferences</h5>
                                <h3 class="mb-0">${data.total_conferences.toLocaleString()}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderTopCited(data, container) {
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info fade-in"><i class="fas fa-info-circle me-2"></i>No cited papers found</div>';
                return;
            }

            container.innerHTML = `
                <div class="data-card card fade-in">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Cited Papers</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th width="80">Rank</th>
                                        <th>Paper Title</th>
                                        <th width="100">Year</th>
                                        <th width="120">Citations</th>
                                        <th width="200">Published In</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map((paper, index) => {
                                        let rankClass = 'rank-default';
                                        if (index === 0) rankClass = 'rank-gold';
                                        else if (index === 1) rankClass = 'rank-silver';
                                        else if (index === 2) rankClass = 'rank-bronze';
                                        
                                        return `
                                            <tr>
                                                <td>
                                                    <span class="rank-badge ${rankClass}">#${index + 1}</span>
                                                </td>
                                                <td><strong>${paper.title}</strong></td>
                                                <td class="text-muted">${paper.publication_year}</td>
                                                <td>
                                                    <span class="badge badge-modern bg-primary">
                                                        <i class="fas fa-quote-right me-1"></i>${paper.citation_count}
                                                    </span>
                                                </td>
                                                <td class="text-muted small">${paper.journal_name || paper.conference_name || 'Other'}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderTopAuthors(data, container) {
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info fade-in"><i class="fas fa-info-circle me-2"></i>No author data found</div>';
                return;
            }

            container.innerHTML = `
                <div class="data-card card fade-in">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-medal me-2"></i>Top Authors by Average Citations</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th width="80">Rank</th>
                                        <th>Author</th>
                                        <th>Institution</th>
                                        <th width="100">Papers</th>
                                        <th width="120">Citations</th>
                                        <th width="120">Avg</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map((author, index) => {
                                        let rankClass = 'rank-default';
                                        if (index === 0) rankClass = 'rank-gold';
                                        else if (index === 1) rankClass = 'rank-silver';
                                        else if (index === 2) rankClass = 'rank-bronze';
                                        
                                        return `
                                            <tr>
                                                <td>
                                                    <span class="rank-badge ${rankClass}">#${index + 1}</span>
                                                </td>
                                                <td>
                                                    <div><strong>${author.name}</strong></div>
                                                    <small class="text-muted"><i class="fas fa-envelope me-1"></i>${author.email}</small>
                                                </td>
                                                <td class="text-muted">${author.institution_name || '<em>Independent</em>'}</td>
                                                <td>
                                                    <span class="badge badge-modern bg-secondary">
                                                        <i class="fas fa-file-alt me-1"></i>${author.paper_count}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-modern bg-primary">
                                                        <i class="fas fa-quote-right me-1"></i>${author.citation_count}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-modern bg-success">
                                                        <i class="fas fa-chart-line me-1"></i>${parseFloat(author.avg_citations).toFixed(1)}
                                                    </span>
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderPublicationStats(data, container) {
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info fade-in"><i class="fas fa-info-circle me-2"></i>No publication data found</div>';
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
                <div class="row g-4 fade-in">
                    <div class="col-12">
                        <div class="data-card card">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Publication Trends Over Time</h5>
                            </div>
                            <div class="card-body" style="padding: 2rem;">
                                <canvas id="publicationChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="data-card card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Detailed Publication Statistics</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-modern mb-0">
                                        <thead>
                                            <tr>
                                                <th>Year</th>
                                                <th>Total Papers</th>
                                                <th>Journal Papers</th>
                                                <th>Conference Papers</th>
                                                <th>Other Papers</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.map(item => `
                                                <tr>
                                                    <td><strong><i class="fas fa-calendar-alt me-2 text-muted"></i>${item.publication_year}</strong></td>
                                                    <td>
                                                        <span class="badge badge-modern bg-primary">
                                                            <i class="fas fa-file-alt me-1"></i>${item.paper_count}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-modern bg-success">
                                                            <i class="fas fa-book me-1"></i>${item.journal_papers}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-modern bg-warning text-dark">
                                                            <i class="fas fa-users me-1"></i>${item.conference_papers}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-modern bg-secondary">
                                                            ${item.paper_count - item.journal_papers - item.conference_papers}
                                                        </span>
                                                    </td>
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

            // Create chart with improved styling
            const ctx = document.getElementById('publicationChart').getContext('2d');
            currentChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: years,
                    datasets: [
                        {
                            label: 'Total Papers',
                            data: paperCounts,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Journal Papers',
                            data: journalCounts,
                            borderColor: '#11998e',
                            backgroundColor: 'rgba(17, 153, 142, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#11998e',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Conference Papers',
                            data: conferenceCounts,
                            borderColor: '#f093fb',
                            backgroundColor: 'rgba(240, 147, 251, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#f093fb',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 13,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            borderColor: '#667eea',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 10
                            },
                            title: {
                                display: true,
                                text: 'Number of Papers',
                                font: {
                                    size: 13,
                                    weight: '600'
                                },
                                padding: 10
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 10
                            },
                            title: {
                                display: true,
                                text: 'Publication Year',
                                font: {
                                    size: 13,
                                    weight: '600'
                                },
                                padding: 10
                            }
                        }
                    }
                }
            });
        }

        function renderInstitutionStats(data, container) {
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-info fade-in"><i class="fas fa-info-circle me-2"></i>No institution data found</div>';
                return;
            }

            container.innerHTML = `
                <div class="data-card card fade-in">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Institution Rankings & Performance</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th width="80">Rank</th>
                                        <th>Institution</th>
                                        <th width="120">Country</th>
                                        <th width="100">Authors</th>
                                        <th width="100">Papers</th>
                                        <th width="120">Citations</th>
                                        <th width="100">Ranking</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map((institution, index) => {
                                        let rankClass = 'rank-default';
                                        if (index === 0) rankClass = 'rank-gold';
                                        else if (index === 1) rankClass = 'rank-silver';
                                        else if (index === 2) rankClass = 'rank-bronze';
                                        
                                        return `
                                            <tr>
                                                <td>
                                                    <span class="rank-badge ${rankClass}">#${index + 1}</span>
                                                </td>
                                                <td>
                                                    <strong>${institution.name}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-modern bg-secondary">
                                                        <i class="fas fa-globe me-1"></i>${institution.country}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-modern bg-info">
                                                        <i class="fas fa-user-graduate me-1"></i>${institution.author_count}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-modern bg-success">
                                                        <i class="fas fa-file-alt me-1"></i>${institution.paper_count}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-modern bg-primary">
                                                        <i class="fas fa-quote-right me-1"></i>${institution.citation_count}
                                                    </span>
                                                </td>
                                                <td class="text-muted">
                                                    ${institution.ranking ? '<strong>#' + institution.ranking + '</strong>' : '<em>Unranked</em>'}
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
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
                <div class="alert alert-danger fade-in" style="border-radius: 15px; border: none; box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1">Error Loading Data</h5>
                            <p class="mb-0">${message}</p>
                        </div>
                    </div>
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