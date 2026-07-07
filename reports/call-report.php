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
$status    = $_GET['status'] ?? '';
$overdue   = $_GET['overdue'] ?? '';

$limit  = 20;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = max($page, 1);
$offset = ($page - 1) * $limit;

/* ----------------------------
   BASE WHERE
----------------------------- */

$where = " WHERE c.call_datetime BETWEEN '$from 00:00:00' AND '$to 23:59:59' ";

if ($role === 'employee') {
    $where .= " AND c.user_id = $userId ";
}

if (!empty($employee) && $role === 'admin') {
    $where .= " AND c.user_id = " . (int)$employee;
}

if (!empty($status)) {
    $where .= " AND c.call_status = '" . $conn->real_escape_string($status) . "' ";
}

if ($overdue === '1') {
    $where .= " AND c.next_followup_date IS NOT NULL 
                AND c.next_followup_date < CURDATE() ";
}

/* ----------------------------
   EXPORT
----------------------------- */

if (isset($_GET['export'])) {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=call-report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        'Call ID',
        'Lead Name',
        'Employee',
        'Call Date',
        'Status',
        'Next Followup',
        'Overdue'
    ]);

    $exportSql = "
        SELECT c.id, l.lead_name,
               u.name AS employee_name,
               c.call_datetime,
               c.call_status,
               c.next_followup_date,
               CASE 
                   WHEN c.next_followup_date IS NOT NULL 
                        AND c.next_followup_date < CURDATE()
                   THEN 'YES'
                   ELSE 'NO'
               END AS overdue
        FROM call_logs c
        LEFT JOIN leads l ON c.lead_id = l.id
        LEFT JOIN users u ON c.user_id = u.id
        $where
        ORDER BY c.call_datetime DESC
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
    SELECT COUNT(c.id) AS total
    FROM call_logs c
    LEFT JOIN leads l ON c.lead_id = l.id
    $where
";

$countResult = $conn->query($countSql);
$totalRows   = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages  = ceil($totalRows / $limit);

/* ----------------------------
   FETCH DATA
----------------------------- */

$sql = "
    SELECT c.id, l.lead_name,
           u.name AS employee_name,
           c.call_datetime,
           c.call_status,
           c.next_followup_date,
           CASE 
               WHEN c.next_followup_date IS NOT NULL 
                    AND c.next_followup_date < CURDATE()
               THEN 1 ELSE 0
           END AS overdue_flag
    FROM call_logs c
    LEFT JOIN leads l ON c.lead_id = l.id
    LEFT JOIN users u ON c.user_id = u.id
    $where
    ORDER BY c.call_datetime DESC
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

/* FETCH DISTINCT CALL STATUS */
$statuses = [];
$statusResult = $conn->query("SELECT DISTINCT call_status FROM call_logs ORDER BY call_status ASC");
while ($row = $statusResult->fetch_assoc()) {
    $statuses[] = $row['call_status'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Call & Follow-up Report</title>
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
    <h3 class="mb-0">calling & follow up Report</h3>
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
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <?php foreach ($statuses as $st): ?>
                    <option value="<?= $st ?>"
                        <?= ($status === $st) ? 'selected' : '' ?>>
                        <?= $st ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label>Overdue</label>
            <select name="overdue" class="form-control">
                <option value="">All</option>
                <option value="1" <?= ($overdue==='1')?'selected':'' ?>>Only Overdue</option>
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a href="?<?= http_build_query(array_merge($_GET, ['export'=>1])) ?>"
               class="btn btn-success">Export</a>
        </div>

    </form>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <p><strong>Total Records:</strong> <?= $totalRows ?></p>

            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Lead</th>
                        <th>Employee</th>
                        <th>Call Date</th>
                        <th>Status</th>
                        <th>Next Followup</th>
                        <th>Overdue</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['lead_name']) ?></td>
                        <td><?= htmlspecialchars($row['employee_name']) ?></td>
                        <td><?= $row['call_datetime'] ?></td>
                        <td><?= htmlspecialchars($row['call_status']) ?></td>
                        <td><?= $row['next_followup_date'] ?></td>
                        <td>
                            <?= $row['overdue_flag'] ? '<span class="text-danger fw-bold">YES</span>' : 'NO' ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>
</body>
</html>