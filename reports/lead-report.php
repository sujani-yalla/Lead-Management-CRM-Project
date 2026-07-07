<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    die("Unauthorized");
}

$role   = $_SESSION['role'];
$userId = $_SESSION['user_id'];

/* ----------------------------
   DEFAULT FILTERS (Current Month)
----------------------------- */

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');
$employee = $_GET['employee'] ?? '';
$source   = $_GET['source'] ?? '';
$search   = $_GET['search'] ?? '';

/* ----------------------------
   PAGINATION
----------------------------- */

$limit = 20;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page  = max($page, 1);
$offset = ($page - 1) * $limit;

/* ----------------------------
   BASE QUERY
----------------------------- */

$where = " WHERE l.created_at BETWEEN '$from 00:00:00' AND '$to 23:59:59' ";

if ($role === 'employee') {
    $where .= " AND l.assigned_to = $userId ";
}

if (!empty($employee) && $role === 'admin') {
    $where .= " AND l.assigned_to = " . (int)$employee;
}

if (!empty($source)) {
    $where .= " AND l.lead_source = '" . $conn->real_escape_string($source) . "' ";
}

if (!empty($search)) {
    $searchEsc = $conn->real_escape_string($search);
    $where .= " AND (l.lead_name LIKE '%$searchEsc%' OR l.mobile LIKE '%$searchEsc%') ";
}


/* ----------------------------
   EXPORT (10K SAFE STREAMING)
----------------------------- */

if (isset($_GET['export'])) {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=lead-report.csv");

    $output = fopen("php://output", "w");

    // CSV HEADERS
    fputcsv($output, [
        'ID',
        'Name',
        'Mobile',
        'Source',
        'Type',
        'Assigned To',
        'Created At'
    ]);

    $exportSql = "
        SELECT l.id, l.lead_name, l.mobile, l.lead_source,
               l.lead_type, l.assigned_to, l.created_at
        FROM leads l
        $where
        ORDER BY l.created_at DESC
    ";

    $exportResult = $conn->query($exportSql);

    while ($row = $exportResult->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}




/* ----------------------------
   TOTAL COUNT
----------------------------- */

$countSql = "SELECT COUNT(l.id) as total FROM leads l $where";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRows / $limit);

/* ----------------------------
   FETCH DATA
----------------------------- */

$sql = "
    SELECT l.id, l.lead_name, l.mobile, l.lead_source,
           l.lead_type, u.name AS employee_name, l.created_at
    FROM leads l
    LEFT JOIN users u ON l.assigned_to = u.id
    $where
    ORDER BY l.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

/* ----------------------------
   FETCH EMPLOYEES (ADMIN ONLY)
----------------------------- */

$employees = [];

if ($role === 'admin') {
    $empResult = $conn->query("SELECT id, name FROM users WHERE role = 'employee' ORDER BY name ASC");
    while ($emp = $empResult->fetch_assoc()) {
        $employees[] = $emp;
    }
}

/* ----------------------------
   FETCH DISTINCT SOURCES
----------------------------- */

$sources = [];

$sourceResult = $conn->query("
    SELECT DISTINCT TRIM(lead_source) AS lead_source
    FROM leads 
    WHERE lead_source IS NOT NULL 
      AND lead_source != ''
    ORDER BY lead_source ASC
");

while ($row = $sourceResult->fetch_assoc()) {
    $sources[] = $row['lead_source'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lead Report</title>
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
    <h3 class="mb-0">Lead Report</h3>
    <a href="reports-dashboard.php" class="btn btn-outline-secondary">
        ← Back to Reports
    </a>
</div>

    <!-- FILTER SECTION -->
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
    <label>Source</label>
    <select name="source" class="form-control">
        <option value="">All Sources</option>
        <?php foreach ($sources as $src): ?>
            <option value="<?= htmlspecialchars($src) ?>"
                <?= ($source === $src) ? 'selected' : '' ?>>
                <?= htmlspecialchars($src) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

        <div class="col-md-2">
            <label>Search</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>">
        </div>

       <div class="col-md-2 d-flex align-items-end gap-2">
    <button class="btn btn-primary w-100">Filter</button>

    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>"
       class="btn btn-success">
       Export
    </a>
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
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Source</th>
                        <th>Type</th>
                        <th>Assigned To</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>

                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['lead_name']) ?></td>
                        <td><?= htmlspecialchars($row['mobile']) ?></td>
                        <td><?= htmlspecialchars($row['lead_source']) ?></td>
                        <td><?= htmlspecialchars($row['lead_type']) ?></td>
                        <td><?= $row['employee_name'] ?></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>

                </tbody>
            </table>
            <!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">

        <?php
        $queryParams = $_GET;
        ?>

        <!-- Previous -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <?php
            $queryParams['page'] = $page - 1;
            ?>
            <a class="page-link"
               href="?<?= http_build_query($queryParams) ?>">
               Previous
            </a>
        </li>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php
            $queryParams['page'] = $i;
            ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link"
                   href="?<?= http_build_query($queryParams) ?>">
                   <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>

        <!-- Next -->
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <?php
            $queryParams['page'] = $page + 1;
            ?>
            <a class="page-link"
               href="?<?= http_build_query($queryParams) ?>">
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