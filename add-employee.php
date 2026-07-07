<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

/* Fetch available modules */
$modulesResult = $conn->query("SELECT id, module_name FROM modules ORDER BY module_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $modules  = $_POST['modules'] ?? [];

    /* Fetch module_key mapping */
$modules = array_map('intval', $_POST['modules'] ?? []);

/* Fetch module_key mapping */
$moduleMap = [];
$mapResult = $conn->query("SELECT id, module_key FROM modules");

while ($row = $mapResult->fetch_assoc()) {
    $moduleMap[$row['module_key']] = (int)$row['id'];
}

/* Trigger modules */
$triggerKeys = [
    'student_visa',
    'work_visa',
    'visitor_visa',
    'pr_application',
    'loan_module'
];

/* Convert trigger keys to IDs */
$triggerIds = array_map(fn($k) => $moduleMap[$k] ?? null, $triggerKeys);
$triggerIds = array_filter($triggerIds);

/* If any trigger selected → auto assign dependent modules */
if (array_intersect($triggerIds, $modules)) {

    $dependentKeys = [
        'lead_management',
        'lead_source_tracking',
        'calling_followup',
        'staff_report'
    ];

    foreach ($dependentKeys as $depKey) {
        if (isset($moduleMap[$depKey])) {
            $modules[] = $moduleMap[$depKey];
        }
    }
}



$modules = array_unique($modules);

    if ($password !== $confirm) {
        $message = "Passwords do not match.";
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        /* Insert Employee */
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, created_at)
            VALUES (?, ?, ?, 'employee', NOW())
        ");
        $stmt->bind_param("sss", $name, $email, $hashedPassword);

        if ($stmt->execute()) {

            $user_id = $stmt->insert_id;

            /* Insert Module Access */
            if (!empty($modules)) {
                $accessStmt = $conn->prepare("
                    INSERT INTO employee_module_access (user_id, module_id)
                    VALUES (?, ?)
                ");

                foreach ($modules as $module_id) {
                    $accessStmt->bind_param("ii", $user_id, $module_id);
                    $accessStmt->execute();
                }
            }

            $message = "Employee created successfully!";
        } else {
            $message = "Error creating employee.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Employee</title>

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
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

.section-title {
    font-weight: 600;
    margin-bottom: 15px;
}

.module-box {
    border: 1px solid #e5e5e5;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    background: #fafafa;
}
</style>

</head>
<body>

<div class="page-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Add New Employee</h4>
    <a href="dashboard.php" class="btn btn-outline-dark btn-sm">← Back to Dashboard</a>
</div>

<div class="container py-4">
    <div class="card card-custom p-4">

        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>

            <hr>

            <div class="section-title mt-4">Module Access</div>

            <div class="row">
                <?php while ($module = $modulesResult->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="module-box">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="modules[]"
                                       value="<?= $module['id'] ?>"
                                       id="module<?= $module['id'] ?>">
                                <label class="form-check-label"
                                       for="module<?= $module['id'] ?>">
                                    <?= htmlspecialchars($module['module_name']) ?>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-dark px-4">
                    Add Employee
                </button>
            </div>

        </form>

    </div>
</div>

</body>
</html>