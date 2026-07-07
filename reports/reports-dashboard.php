<?php
session_start();
require_once "../db.php";
require_once "report-queries.php";

$role = $_SESSION['role'];
$summary = getDashboardSummary();

$dashboardLink = ($role === 'admin') 
    ? '../dashboard.php' 
    : '../employee-dashboard.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <style>
        body {
            background-color: #f4f6f9;
        }

        /* Summary Cards */
        .summary-card {
            border: none;
            border-radius: 16px;
            padding: 18px;
            background: #ffffff;
            transition: 0.25s ease;
        }

        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
        }

        .summary-title {
            font-size: 13px;
            color: #6c757d;
        }

        .summary-value {
            font-size: 24px;
            font-weight: 600;
        }

        /* Report Tiles */
        .report-tile {
            border: none;
            border-radius: 18px;
            padding: 22px;
            background: #ffffff;
            transition: all 0.25s ease;
            height: 100%;
        }

        .report-tile:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 30px rgba(0,0,0,0.08);
        }

        .icon-box {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .bg-primary { background: #2563eb; }
        .bg-success { background: #16a34a; }
        .bg-warning { background: #f59e0b; }
        .bg-info    { background: #0ea5e9; }
        .bg-dark    { background: #334155; }

        .section-title {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #6c757d;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<div class="container-fluid p-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Reports Dashboard</h3>
        <a href="<?= $dashboardLink ?>" class="btn btn-outline-secondary">
            ← Back to Dashboard
        </a>
    </div>

    <!-- SUMMARY SECTION -->
    <div class="row g-4">

        <div class="col-md-4 col-lg-2">
            <div class="summary-card shadow-sm">
                <div class="summary-title">Total Leads</div>
                <div class="summary-value text-primary">
                    <?= $summary['total_leads']; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="summary-card shadow-sm">
                <div class="summary-title">Total Visas</div>
                <div class="summary-value text-success">
                    <?= $summary['total_visas']; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="summary-card shadow-sm">
                <div class="summary-title">PR Collected</div>
                <div class="summary-value text-warning">
                    ₹ <?= number_format($summary['pr_revenue'], 2); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="summary-card shadow-sm">
                <div class="summary-title">Loan Disbursed</div>
                <div class="summary-value text-info">
                    ₹ <?= number_format($summary['loan_disbursed'], 2); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="summary-card shadow-sm">
                <div class="summary-title">Calls This Month</div>
                <div class="summary-value">
                    <?= $summary['calls_this_month']; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-2">
            <div class="summary-card shadow-sm">
                <div class="summary-title">Overdue Followups</div>
                <div class="summary-value text-danger">
                    <?= $summary['overdue_followups']; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- OPERATIONAL REPORTS -->
    <div class="section-title">Operational Reports</div>
    <hr>

    <div class="row g-4">

        <!-- Lead Report -->
        <div class="col-md-4">
            <a href="lead-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary">
                            <i data-feather="users"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">Lead Report</h6>
                            <small class="text-muted">Lead data, filters & export</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Student Report -->
        <div class="col-md-4">
            <a href="student-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success">
                            <i data-feather="book-open"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">Student Report</h6>
                            <small class="text-muted">Student visa records & export</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Visa Pipeline -->
        <div class="col-md-4">
            <a href="visa-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-info">
                            <i data-feather="activity"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">Visa Pipeline Report</h6>
                            <small class="text-muted">All visa types overview</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- CA & Legal -->
        <div class="col-md-4">
            <a href="ca-legal-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-dark">
                            <i data-feather="file-text"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">CA & Legal Report</h6>
                            <small class="text-muted">Legal & compliance tracking</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Source Performance -->
        <div class="col-md-4">
            <a href="source-performance-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-warning">
                            <i data-feather="trending-up"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">Source Performance</h6>
                            <small class="text-muted">Marketing performance analytics</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <?php if ($role === 'admin'): ?>

    <!-- FINANCIAL & MANAGEMENT -->
    <div class="section-title">Financial & Management Reports</div>
    <hr>

    <div class="row g-4">

        <div class="col-md-4">
            <a href="pr-revenue-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success">
                            <i data-feather="dollar-sign"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">PR Revenue Report</h6>
                            <small class="text-muted">PR payments & collection summary</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="loan-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-info">
                            <i data-feather="credit-card"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">Loan Financial Report</h6>
                            <small class="text-muted">Loan disbursement & status</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="employee-performance-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary">
                            <i data-feather="bar-chart-2"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">Employee Performance</h6>
                            <small class="text-muted">Staff productivity insights</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="call-report.php" class="text-decoration-none">
                <div class="report-tile shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-warning">
                            <i data-feather="phone-call"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-dark">Call & Follow-up Report</h6>
                            <small class="text-muted">Call logs & follow-ups</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <?php endif; ?>

</div>

<script>
    feather.replace();
</script>

</body>
</html>