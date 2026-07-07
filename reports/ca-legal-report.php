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

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');
$service = $_GET['service'] ?? '';
$status  = $_GET['status'] ?? '';
$employee = $_GET['employee'] ?? '';
$search = $_GET['search'] ?? '';

/* ----------------------------
   BASE WHERE
----------------------------- */

$where = " WHERE lc.created_at BETWEEN '$from 00:00:00' AND '$to 23:59:59' ";

if ($role === 'employee') {
    $where .= " AND lc.created_by = $userId ";
}

if ($role === 'admin' && !empty($employee)) {
    $where .= " AND lc.created_by = " . (int)$employee;
}

if (!empty($service)) {
    $where .= " AND lc.service_type = '" . $conn->real_escape_string($service) . "' ";
}

if (!empty($status)) {
    $where .= " AND lc.completion_status = '" . $conn->real_escape_string($status) . "' ";
}

if (!empty($search)) {
    $searchEsc = $conn->real_escape_string($search);
    $where .= " AND lc.client_name LIKE '%$searchEsc%' ";
}

/* ----------------------------
   EXPORT
----------------------------- */

if (isset($_GET['export'])) {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=ca-legal-report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        'ID',
        'Client',
        'Service',
        'Documents Status',
        'Completion Status',
        'Employee',
        'Created At'
    ]);

    $exportSql = "
        SELECT lc.id, lc.client_name, lc.service_type,
               lc.documents_status, lc.completion_status,
               u.name, lc.created_at
        FROM legal_cases lc
        LEFT JOIN users u ON lc.created_by = u.id
        $where
        ORDER BY lc.created_at DESC
    ";

    $result = $conn->query($exportSql);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/* ----------------------------
   PAGINATION
----------------------------- */

$limit = 20;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page  = max($page, 1);
$offset = ($page - 1) * $limit;

/* ----------------------------
   TOTAL COUNT
----------------------------- */

$countSql = "SELECT COUNT(lc.id) as total FROM legal_cases lc $where";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRows / $limit);

/* ----------------------------
   FETCH DATA
----------------------------- */

$sql = "
    SELECT lc.id, lc.client_name, lc.service_type,
           lc.documents_status, lc.completion_status,
           u.name AS employee_name, lc.created_at
    FROM legal_cases lc
    LEFT JOIN users u ON lc.created_by = u.id
    $where
    ORDER BY lc.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

/* ----------------------------
   FETCH EMPLOYEES (ADMIN)
----------------------------- */

$employees = [];

if ($role === 'admin') {
    $empResult = $conn->query("SELECT id, name FROM users WHERE role='employee'");
    while ($emp = $empResult->fetch_assoc()) {
        $employees[] = $emp;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>CA & Legal Report</title>
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
    <h3 class="mb-0">CA & Legal Report</h3>
    <a href="reports-dashboard.php" class="btn btn-outline-secondary">
        ← Back to Reports
    </a>

</div>

    <!-- FILTER SECTION -->
    <form method="GET" class="row g-3 mb-4 align-items-end">

    <div class="col-md-2">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= $from ?>">
    </div>

    <div class="col-md-2">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= $to ?>">
    </div>

    <?php if ($role === 'admin'): ?>
    <div class="col-md-2">
        <label class="form-label">Employee</label>
        <select name="employee" class="form-control">
            <option value="">All Employees</option>
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
        <label class="form-label">Service</label>
        <select name="service" class="form-control">
            <option value="">All</option>
            <option value="ca" <?= ($service === 'ca') ? 'selected' : '' ?>>CA</option>
            <option value="legal" <?= ($service === 'legal') ? 'selected' : '' ?>>Legal</option>
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label">Status</label>
        <input type="text" name="status" class="form-control" value="<?= htmlspecialchars($status) ?>">
    </div>

    <div class="col-md-2">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary w-100">Filter</button>
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>"
           class="btn btn-success w-100">Export</a>
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
                        <th>Client</th>
                        <th>Service</th>
                        <th>Documents</th>
                        <th>Status</th>
                        <th>Employee</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>

                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['client_name']) ?></td>
                        <td><?= htmlspecialchars($row['service_type']) ?></td>
                        <td><?= htmlspecialchars($row['documents_status']) ?></td>
                        <td><?= htmlspecialchars($row['completion_status']) ?></td>
                        <td><?= htmlspecialchars($row['employee_name']) ?></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>

                </tbody>
            </table>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">

                    <?php $queryParams = $_GET; ?>

                    <!-- Previous -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <?php $queryParams['page'] = $page - 1; ?>
                        <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                            Previous
                        </a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $queryParams['page'] = $i; ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <?php $queryParams['page'] = $page + 1; ?>
                        <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                            Next
                        </a>
                    </li>

                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>