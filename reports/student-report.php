<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    die("Unauthorized");
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];

/* ---------------- FILTERS ---------------- */

$from        = $_GET['from'] ?? '';
$to          = $_GET['to'] ?? '';
$employee    = $_GET['employee'] ?? '';
$visa_status = $_GET['visa_status'] ?? '';

$where = "WHERE v.visa_type = 'student'";
$params = [];
$types  = "";

/* Date Filter */
if (!empty($from) && !empty($to)) {
    $where .= " AND DATE(v.created_at) BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types .= "ss";
}

/* Visa Status Filter */
if (!empty($visa_status)) {
    $where .= " AND v.visa_status = ?";
    $params[] = $visa_status;
    $types .= "s";
}

/* Role Restriction */
if ($role === 'employee') {
    $where .= " AND l.assigned_to = ?";
    $params[] = $userId;
    $types .= "i";
} elseif ($role === 'admin' && !empty($employee)) {
    $where .= " AND l.assigned_to = ?";
    $params[] = $employee;
    $types .= "i";
}

/* ---------------- MAIN QUERY ---------------- */

$sql = "
SELECT 
    v.id,
    l.lead_name,
    v.status,
    v.visa_status,
    u.name AS employee_name,
    v.created_at
FROM visas v
JOIN leads l ON v.lead_id = l.id
LEFT JOIN users u ON l.assigned_to = u.id
$where
ORDER BY v.id DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/* ---------------- EXPORT CSV ---------------- */

if (isset($_GET['export']) && $_GET['export'] == 1) {

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_report_' . date('Y-m-d') . '.csv"');

    $output = fopen("php://output", "w");

    // CSV Header Row
    fputcsv($output, [
        'ID',
        'Lead Name',
        'Processing Status',
        'Visa Decision',
        'Employee',
        'Created Date'
    ]);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['lead_name'],
            $row['status'],
            $row['visa_status'],
            $row['employee_name'],
            $row['created_at']
        ]);
    }

    fclose($output);
    exit;
}

$totalRecords = $result->num_rows;
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Student Visa Report</h3>

    <a href="reports-dashboard.php" class="btn btn-outline-secondary">
        ← Back to Reports
    </a>
</div>

    <!-- FILTER -->
    <form method="GET" class="row g-3 mb-4">

    <div class="col-md-2">
        <label>From</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
    </div>

    <div class="col-md-2">
        <label>To</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
    </div>

    <?php if ($role === 'admin'): ?>
    <div class="col-md-2">
        <label>Employee</label>
        <select name="employee" class="form-control">
            <option value="">All</option>
            <?php
            $empList = $conn->query("SELECT id, name FROM users WHERE role='employee'");
            while ($emp = $empList->fetch_assoc()):
            ?>
                <option value="<?= $emp['id'] ?>"
                    <?= ($employee == $emp['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($emp['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="col-md-2">
        <label>Visa Status</label>
        <select name="visa_status" class="form-control">
            <option value="">All</option>
            <option value="Approved" <?= $visa_status=='Approved'?'selected':'' ?>>Approved</option>
            <option value="Rejected" <?= $visa_status=='Rejected'?'selected':'' ?>>Rejected</option>
            <option value="Processing" <?= $visa_status=='Processing'?'selected':'' ?>>Processing</option>
        </select>
    </div>

    <div class="col-md-2 align-self-end">
        <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Filter</button>

    <button type="submit"
            name="export"
            value="1"
            class="btn btn-success">
        Export CSV
    </button>
</div>
    </div>

</form>

    <!-- TABLE -->
    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <div class="mb-3">
    <strong>Total Records:</strong> <?= $totalRecords ?>
</div>

            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                         <th>ID</th>
                         <th>Lead</th>
                         <th>Status</th>
                         <th>Visa Decision</th>
                         <th>Employee</th>
                         <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['lead_name']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
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