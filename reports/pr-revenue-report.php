<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    die("Unauthorized");
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];

/* ----------------------------
   DEFAULT FILTERS
----------------------------- */

$from      = $_GET['from'] ?? date('Y-m-01');
$to        = $_GET['to'] ?? date('Y-m-d');
$employee  = $_GET['employee'] ?? '';
$search    = $_GET['search'] ?? '';

/* ----------------------------
   PAGINATION
----------------------------- */

$limit  = 20;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = max($page, 1);
$offset = ($page - 1) * $limit;

/* ----------------------------
   BASE WHERE
----------------------------- */

$where = " WHERE p.payment_date BETWEEN '$from' AND '$to' ";

if ($role === 'employee') {
    $where .= " AND l.assigned_to = $userId ";
}

if (!empty($employee) && $role === 'admin') {
    $where .= " AND l.assigned_to = " . (int)$employee;
}

if (!empty($search)) {
    $searchEsc = $conn->real_escape_string($search);
    $where .= " AND l.lead_name LIKE '%$searchEsc%' ";
}

/* ----------------------------
   TOTAL COLLECTION
----------------------------- */

$totalSql = "
    SELECT IFNULL(SUM(p.amount),0) AS total
    FROM pr_payments p
    LEFT JOIN leads l ON p.lead_id = l.id
    $where
";

$totalResult = $conn->query($totalSql);
$totalAmount = $totalResult->fetch_assoc()['total'] ?? 0;

/* ----------------------------
   EXPORT
----------------------------- */

if (isset($_GET['export'])) {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=pr-revenue-report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        'Payment ID',
        'Lead Name',
        'Employee',
        'Payment Date',
        'Amount'
    ]);

    $exportSql = "
        SELECT p.id, l.lead_name,
               u.name AS employee_name,
               p.payment_date, p.amount
        FROM pr_payments p
        LEFT JOIN leads l ON p.lead_id = l.id
        LEFT JOIN users u ON l.assigned_to = u.id
        $where
        ORDER BY p.payment_date DESC
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
    SELECT COUNT(p.id) AS total
    FROM pr_payments p
    LEFT JOIN leads l ON p.lead_id = l.id
    $where
";

$countResult = $conn->query($countSql);
$totalRows   = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages  = ceil($totalRows / $limit);

/* ----------------------------
   FETCH DATA
----------------------------- */

$sql = "
    SELECT p.id, l.lead_name,
           u.name AS employee_name,
           p.payment_date, p.amount
    FROM pr_payments p
    LEFT JOIN leads l ON p.lead_id = l.id
    LEFT JOIN users u ON l.assigned_to = u.id
    $where
    ORDER BY p.payment_date DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

/* ----------------------------
   FETCH EMPLOYEES (ADMIN)
----------------------------- */

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
    <title>PR Revenue Report</title>
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
    <h3 class="mb-0">PR Revenue Report</h3>
    <a href="reports-dashboard.php" class="btn btn-outline-secondary">
        ← Back to Reports
    </a>
</div>

    <!-- FILTER -->
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
            <label>Lead Name</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export'=>1])) ?>"
               class="btn btn-success">Export</a>
        </div>

    </form>

    <!-- TOTAL -->
    <div class="alert alert-success">
        <strong>Total Collected:</strong> ₹ <?= number_format($totalAmount,2) ?>
    </div>

    <!-- TABLE -->
    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <p><strong>Total Records:</strong> <?= $totalRows ?></p>

            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Lead</th>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['lead_name']) ?></td>
                        <td><?= htmlspecialchars($row['employee_name']) ?></td>
                        <td><?= $row['payment_date'] ?></td>
                        <td>₹ <?= number_format($row['amount'],2) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>
</body>
</html>