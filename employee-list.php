<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['role'];
$limit = 10; // employees per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$moduleFilter = $_GET['module_filter'] ?? '';

$query = "
SELECT 
    u.id, 
    u.name, 
    u.email, 
    u.created_at,
    GROUP_CONCAT(m.module_name ORDER BY m.module_name SEPARATOR '||') AS modules
FROM users u
LEFT JOIN employee_module_access ema ON u.id = ema.user_id
LEFT JOIN modules m ON ema.module_id = m.id
WHERE u.role = 'employee'
";

$params = [];
$types  = "";

/* Search condition */
if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

/* Module filter */
if (!empty($moduleFilter)) {
    $query .= " AND ema.module_id = ?";
    $params[] = $moduleFilter;
    $types .= "i";
}

$countQuery = "
SELECT COUNT(DISTINCT u.id) as total
FROM users u
LEFT JOIN employee_module_access ema ON u.id = ema.user_id
WHERE u.role = 'employee'
";

$countParams = [];
$countTypes  = "";

/* Search */
if (!empty($search)) {
    $countQuery .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countTypes .= "ss";
}

/* Module filter */
if (!empty($moduleFilter)) {
    $countQuery .= " AND ema.module_id = ?";
    $countParams[] = $moduleFilter;
    $countTypes .= "i";
}

$countStmt = $conn->prepare($countQuery);

if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];

$totalPages = ceil($totalRows / $limit);

$query .= " GROUP BY u.id ";

$query .= " ORDER BY u.created_at DESC";

$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employee Management</title>

<!-- Bootstrap 5 CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background-color: #f4f6f9;
}

.page-header {
    background: #ffffff;
    padding: 20px 30px;
    border-bottom: 1px solid #e5e5e5;
}

.card-custom {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}

.table thead {
    background-color: #f8f9fa;
}

.badge-module {
    background-color: #e9ecef;
    color: #333;
    font-size: 12px;
    margin: 2px;
}
</style>

</head>
<body>

<div class="page-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Employee Management</h4>
    <a href="add-employee.php" class="btn btn-dark btn-sm">+ Add Employee</a>
</div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Employee List</h4>
    <a href="dashboard.php" class="btn btn-outline-dark btn-sm">
        ← Back to Dashboard
    </a>
</div>
    <div class="card card-custom p-4">
        <div class="table-responsive">

        <form method="GET" class="row g-2 mb-3">

    <div class="col-md-4">
        <input type="text"
               name="search"
               class="form-control"
               placeholder="Search by name or email"
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    </div>

    <div class="col-md-3">
        <select name="module_filter" class="form-select">
            <option value="">Filter by Module</option>
            <?php
            $moduleList = $conn->query("SELECT id, module_name FROM modules ORDER BY module_name ASC");
            while ($m = $moduleList->fetch_assoc()):
            ?>
                <option value="<?= $m['id'] ?>"
                    <?= (isset($_GET['module_filter']) && $_GET['module_filter'] == $m['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['module_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-2">
        <button type="submit" class="btn btn-dark w-100">Apply</button>
    </div>

    <div class="col-md-2">
        <a href="employee-list.php" class="btn btn-secondary w-100">Reset</a>
    </div>

</form>
            
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Modules</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>

<?php 
$i = $offset + 1; 
while ($row = $result->fetch_assoc()): 
?>

<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td>
<?php 
if (!empty($row['modules'])) {
    $moduleNames = explode('||', $row['modules']);
    foreach ($moduleNames as $mod) {
        echo '<span class="badge badge-module">'.htmlspecialchars($mod).'</span>';
    }
} else {
    echo '<span class="text-muted">No Access</span>';
}
?>
</td>
    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
   <td class="text-center">

    

                                   <!-- EDIT BUTTON (Employee + Admin) -->
                                    <a href="edit-employee.php?id=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-outline-success rounded-2 px-2">
                                       <i data-feather="edit" style="width:14px;height:14px;"></i>
                                    </a>

                                    <?php if ($role === 'admin') { ?>
                                        <!-- DELETE BUTTON (Admin Only) -->
                                        <form action="delete-employee.php" method="POST"
                                           onsubmit="return confirm('Are you sure you want to delete this record?');"
                                           style="display:inline;">

                                          <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                         <button type="submit"
                                                 class="btn btn-sm btn-outline-danger rounded-2 px-2">
                                               <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                         </button>
                                       </form>
                                    <?php } ?>

                                

</td>
</tr>

<?php endwhile; ?>

                </tbody>
            </table>
         
            <?php if ($totalPages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">

        <!-- Previous -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link"
               href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
               Previous
            </a>
        </li>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link"
                   href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                   <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>

        <!-- Next -->
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link"
               href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
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