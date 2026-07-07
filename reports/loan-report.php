<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    die("Unauthorized");
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];

$from      = $_GET['from'] ?? date('Y-m-01');
$to        = $_GET['to'] ?? date('Y-m-d');
$employee  = $_GET['employee'] ?? '';
$disbursed = $_GET['disbursed'] ?? '';

$limit  = 20;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = max($page, 1);
$offset = ($page - 1) * $limit;

/* ----------------------------
   BASE WHERE
----------------------------- */

$where = " WHERE lo.created_at BETWEEN '$from 00:00:00' AND '$to 23:59:59' ";

if ($role === 'employee') {
    $where .= " AND l.assigned_to = $userId ";
}

if (!empty($employee) && $role === 'admin') {
    $where .= " AND l.assigned_to = " . (int)$employee;
}

if ($disbursed === '1') {
    $where .= " AND lo.loan_disbursement_amount IS NOT NULL ";
}

/* ----------------------------
   TOTALS
----------------------------- */

$totalSql = "
    SELECT 
        IFNULL(SUM(lo.loan_sanctioned_amount),0) AS total_sanctioned,
        IFNULL(SUM(lo.loan_disbursement_amount),0) AS total_disbursed
    FROM loans lo
    LEFT JOIN leads l ON lo.lead_id = l.id
    $where
";

$totalResult = $conn->query($totalSql);
$totals = $totalResult->fetch_assoc();

$totalSanctioned = $totals['total_sanctioned'] ?? 0;
$totalDisbursed  = $totals['total_disbursed'] ?? 0;

/* ----------------------------
   EXPORT
----------------------------- */

if (isset($_GET['export'])) {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=loan-report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        'Loan ID',
        'Lead Name',
        'Employee',
        'Sanctioned Amount',
        'Disbursed Amount',
        'Sanction Date',
        'Disbursement Date'
    ]);

    $exportSql = "
        SELECT lo.id, l.lead_name,
               u.name AS employee_name,
               lo.loan_sanctioned_amount,
               lo.loan_disbursement_amount,
               lo.loan_sanctioned_date,
               lo.loan_disbursement_date
        FROM loans lo
        LEFT JOIN leads l ON lo.lead_id = l.id
        LEFT JOIN users u ON l.assigned_to = u.id
        $where
        ORDER BY lo.created_at DESC
    ";

    $result = $conn->query($exportSql);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/* ----------------------------
   TOTAL COUNT
----------------------------- */

$countSql = "
    SELECT COUNT(lo.id) AS total
    FROM loans lo
    LEFT JOIN leads l ON lo.lead_id = l.id
    $where
";

$countResult = $conn->query($countSql);
$totalRows   = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages  = ceil($totalRows / $limit);

/* ----------------------------
   FETCH DATA
----------------------------- */

$sql = "
    SELECT lo.id, l.lead_name,
           u.name AS employee_name,
           lo.loan_sanctioned_amount,
           lo.loan_disbursement_amount,
           lo.loan_sanctioned_date,
           lo.loan_disbursement_date
    FROM loans lo
    LEFT JOIN leads l ON lo.lead_id = l.id
    LEFT JOIN users u ON l.assigned_to = u.id
    $where
    ORDER BY lo.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

/* FETCH EMPLOYEES (ADMIN) */
$employees = [];
if ($role === 'admin') {
    $empResult = $conn->query("SELECT id, name FROM users WHERE role='employee' ORDER BY name ASC");
    while ($row = $empResult->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Financial Report</title>
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
    <h3 class="mb-0">Loan Report</h3>
    <a href="reports-dashboard.php" class="btn btn-outline-secondary">
        ← Back to Reports
    </a>
</div>

    <form method="GET" class="row g-3 mb-4">

        <div class="col-md-2">
            <label>From</label>
            <input type="date" name="from" class="form-control" value="<?= $from ?>">
        </div>

        <div class="col-md-2">
            <label>To</label>
            <input type="date" name="to" class="form-control" value="<?= $to ?>">
        </div>

        <?php if ($role === 'admin'): ?>
        <div class="col-md-2">
            <label>Employee</label>
            <select name="employee" class="form-control">
                <option value="">All</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>"
                        <?= ($employee == $emp['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="col-md-2">
            <label>Disbursed Only</label>
            <select name="disbursed" class="form-control">
                <option value="">All</option>
                <option value="1" <?= ($disbursed==='1')?'selected':'' ?>>Yes</option>
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export'=>1])) ?>"
               class="btn btn-success">Export</a>
        </div>

    </form>

    <!-- TOTALS -->
    <div class="alert alert-info">
        <strong>Total Sanctioned:</strong> ₹ <?= number_format($totalSanctioned,2) ?>
        &nbsp; | &nbsp;
        <strong>Total Disbursed:</strong> ₹ <?= number_format($totalDisbursed,2) ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <p><strong>Total Records:</strong> <?= $totalRows ?></p>

            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Lead</th>
                        <th>Employee</th>
                        <th>Sanctioned</th>
                        <th>Disbursed</th>
                        <th>Sanction Date</th>
                        <th>Disbursement Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['lead_name']) ?></td>
                        <td><?= htmlspecialchars($row['employee_name']) ?></td>
                        <td>₹ <?= number_format($row['loan_sanctioned_amount'],2) ?></td>
                        <td>₹ <?= number_format($row['loan_disbursement_amount'],2) ?></td>
                        <td><?= $row['loan_sanctioned_date'] ?></td>
                        <td><?= $row['loan_disbursement_date'] ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>
</body>
</html>