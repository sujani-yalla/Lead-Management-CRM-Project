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

$from       = $_GET['from'] ?? date('Y-m-01');
$to         = $_GET['to'] ?? date('Y-m-d');
$employee   = $_GET['employee'] ?? '';
$visa_type  = $_GET['visa_type'] ?? '';
$visa_status= $_GET['visa_status'] ?? '';

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

$where = " WHERE v.created_at BETWEEN '$from 00:00:00' AND '$to 23:59:59' ";

if ($role === 'employee') {
    $where .= " AND l.assigned_to = $userId ";
}

if (!empty($employee) && $role === 'admin') {
    $where .= " AND l.assigned_to = " . (int)$employee;
}

if (!empty($visa_type)) {
    $where .= " AND v.visa_type = '" . $conn->real_escape_string($visa_type) . "' ";
}

if (!empty($visa_status)) {
    $where .= " AND v.visa_status = '" . $conn->real_escape_string($visa_status) . "' ";
}

/* ----------------------------
   EXPORT (10K SAFE)
----------------------------- */

if (isset($_GET['export'])) {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=visa-report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        'Visa ID',
        'Lead Name',
        'Visa Type',
        'Visa Status',
        'Employee',
        'Created At'
    ]);

    $exportSql = "
        SELECT v.id, l.lead_name, v.visa_type, v.visa_status,
               u.name AS employee_name, v.created_at
        FROM visas v
        LEFT JOIN leads l ON v.lead_id = l.id
        LEFT JOIN users u ON l.assigned_to = u.id
        $where
        ORDER BY v.created_at DESC
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
    SELECT COUNT(v.id) AS total
    FROM visas v
    LEFT JOIN leads l ON v.lead_id = l.id
    $where
";

$countResult = $conn->query($countSql);
$totalRows   = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages  = ceil($totalRows / $limit);

/* ----------------------------
   FETCH DATA
----------------------------- */

$sql = "
    SELECT v.id, l.lead_name, v.visa_type, v.visa_status,
           u.name AS employee_name, v.created_at
    FROM visas v
    LEFT JOIN leads l ON v.lead_id = l.id
    LEFT JOIN users u ON l.assigned_to = u.id
    $where
    ORDER BY v.created_at DESC
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

/* ----------------------------
   FETCH DISTINCT TYPES & STATUS
----------------------------- */

$types = [];
$typeResult = $conn->query("SELECT DISTINCT visa_type FROM visas ORDER BY visa_type ASC");
while ($row = $typeResult->fetch_assoc()) {
    $types[] = $row['visa_type'];
}

$statuses = [];
$statusResult = $conn->query("SELECT DISTINCT visa_status FROM visas ORDER BY visa_status ASC");
while ($row = $statusResult->fetch_assoc()) {
    $statuses[] = $row['visa_status'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Visa Pipeline Report</title>
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
    <h3 class="mb-0">Visa Pipeline Report</h3>
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
            <label>Visa Type</label>
            <select name="visa_type" class="form-control">
                <option value="">All</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= $type ?>"
                        <?= ($visa_type === $type) ? 'selected' : '' ?>>
                        <?= $type ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label>Status</label>
            <select name="visa_status" class="form-control">
                <option value="">All</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= $status ?>"
                        <?= ($visa_status === $status) ? 'selected' : '' ?>>
                        <?= $status ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export'=>1])) ?>"
               class="btn btn-success">Export</a>
        </div>

    </form>

    <!-- TABLE -->
    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <p><strong>Total Records:</strong> <?= $totalRows ?></p>

            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Lead</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Employee</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['lead_name']) ?></td>
                        <td><?= htmlspecialchars($row['visa_type']) ?></td>
                        <td><?= htmlspecialchars($row['visa_status']) ?></td>
                        <td><?= htmlspecialchars($row['employee_name']) ?></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>
</body>
</html>