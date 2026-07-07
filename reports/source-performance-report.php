<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    die("Unauthorized");
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

/* ----------------------------
   BASE WHERE
----------------------------- */

$where = " WHERE l.created_at BETWEEN '$from 00:00:00' AND '$to 23:59:59' ";

if ($role === 'employee') {
    $where .= " AND l.assigned_to = $userId ";
}

/* ----------------------------
   MAIN AGGREGATION QUERY
----------------------------- */

$sql = "
SELECT 
    l.lead_source,

    COUNT(DISTINCT l.id) AS total_leads,

    COUNT(DISTINCT v.id) AS total_visas,

    IFNULL(SUM(DISTINCT p.amount),0) AS pr_revenue,

    IFNULL(SUM(DISTINCT lo.loan_disbursement_amount),0) AS loan_disbursed

FROM leads l

LEFT JOIN visas v ON v.lead_id = l.id
LEFT JOIN pr_payments p ON p.lead_id = l.id
LEFT JOIN loans lo ON lo.lead_id = l.id

$where

GROUP BY l.lead_source
ORDER BY total_leads DESC
";

$result = $conn->query($sql);

/* ----------------------------
   EXPORT
----------------------------- */

if (isset($_GET['export'])) {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=source-performance-report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        'Source',
        'Leads',
        'Visas',
        'PR Revenue',
        'Loan Disbursed',
        'Conversion %'
    ]);

    while ($row = $result->fetch_assoc()) {

        $conversion = 0;
        if ($row['total_leads'] > 0) {
            $conversion = ($row['total_visas'] / $row['total_leads']) * 100;
        }

        fputcsv($output, [
            $row['lead_source'],
            $row['total_leads'],
            $row['total_visas'],
            $row['pr_revenue'],
            $row['loan_disbursed'],
            round($conversion,2)
        ]);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Source Performance Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
body {
    background-color: #f8fafc;
    font-family: 'Segoe UI', sans-serif;
}

.card {
    border: none;
    border-radius: 12px;
}

.table thead {
    background-color: #f1f5f9;
}

.btn-primary {
    background-color: #2563eb;
    border: none;
}

.btn-success {
    background-color: #16a34a;
    border: none;
}

.btn-outline-secondary {
    border-radius: 8px;
}

.pagination .page-item.active .page-link {
    background-color: #2563eb;
    border-color: #2563eb;
}
</style>
</head>
<body>

<div class="container-fluid p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Marketing Source Performance Report</h3>
    <a href="reports-dashboard.php" class="btn btn-outline-secondary">
        ← Back to Reports
    </a>
</div>
    <form method="GET" class="row g-3 mb-4">

        <div class="col-md-3">
            <label>From</label>
            <input type="date" name="from" class="form-control" value="<?= $from ?>">
        </div>

        <div class="col-md-3">
            <label>To</label>
            <input type="date" name="to" class="form-control" value="<?= $to ?>">
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export'=>1])) ?>"
               class="btn btn-success">Export</a>
        </div>

    </form>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Source</th>
                        <th>Leads</th>
                        <th>Visas</th>
                        <th>PR Revenue</th>
                        <th>Loan Disbursed</th>
                        <th>Conversion %</th>
                    </tr>
                </thead>
                <tbody>

                <?php while ($row = $result->fetch_assoc()): 
                    
                    $conversion = 0;
                    if ($row['total_leads'] > 0) {
                        $conversion = ($row['total_visas'] / $row['total_leads']) * 100;
                    }
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['lead_source']) ?></td>
                        <td><?= $row['total_leads'] ?></td>
                        <td><?= $row['total_visas'] ?></td>
                        <td>₹ <?= number_format($row['pr_revenue'],2) ?></td>
                        <td>₹ <?= number_format($row['loan_disbursed'],2) ?></td>
                        <td><?= round($conversion,2) ?>%</td>
                    </tr>
                <?php endwhile; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

</body>
</html>