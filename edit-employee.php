<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: employee-list.php");
    exit;
}

$user_id = intval($_GET['id']);
$message = "";

/* Fetch employee details */
$userStmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'employee'");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows !== 1) {
    header("Location: employee-list.php");
    exit;
}

$user = $userResult->fetch_assoc();

/* Fetch all modules */
$modulesResult = $conn->query("SELECT id, module_key, module_name FROM modules ORDER BY module_name ASC");

/* Fetch assigned modules */
$assigned = [];
$assignedStmt = $conn->prepare("SELECT module_id FROM employee_module_access WHERE user_id = ?");
$assignedStmt->bind_param("i", $user_id);
$assignedStmt->execute();
$assignedResult = $assignedStmt->get_result();

while ($row = $assignedResult->fetch_assoc()) {
    $assigned[] = (int)$row['module_id'];
}

/* HANDLE UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $modules  = array_map('intval', $_POST['modules'] ?? []);

    /* Password update (optional) */
    if (!empty($password)) {
        if ($password !== $confirm) {
            $message = "Passwords do not match.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $passStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $passStmt->bind_param("si", $hashedPassword, $user_id);
            $passStmt->execute();
        }
    }

    /* Update name & email */
    $updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $name, $email, $user_id);
    $updateStmt->execute();

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

    $triggerIds = array_map(fn($k) => $moduleMap[$k] ?? null, $triggerKeys);
    $triggerIds = array_filter($triggerIds);

    /* Apply dependency logic */
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

    /* Delete old access */
    $deleteStmt = $conn->prepare("DELETE FROM employee_module_access WHERE user_id = ?");
    $deleteStmt->bind_param("i", $user_id);
    $deleteStmt->execute();

    /* Insert updated modules */
    if (!empty($modules)) {
        $insertStmt = $conn->prepare("
            INSERT INTO employee_module_access (user_id, module_id)
            VALUES (?, ?)
        ");

        foreach ($modules as $module_id) {
            $insertStmt->bind_param("ii", $user_id, $module_id);
            $insertStmt->execute();
        }
    }

    $message = "Employee updated successfully!";
    $assigned = $modules;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Employee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">
<div class="card p-4 shadow-sm">

<h4>Edit Employee</h4>

<?php if ($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST">

<div class="row mb-3">
<div class="col-md-6">
<label>Name</label>
<input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
</div>

<div class="col-md-6">
<label>Email</label>
<input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
</div>
</div>

<hr>

<h6>Change Password (Optional)</h6>

<div class="row mb-3">
<div class="col-md-6">
<label>New Password</label>
<input type="password" id="passwordField" name="password" class="form-control">
</div>

<div class="col-md-6">
<label>Confirm Password</label>
<input type="password" name="confirm_password" class="form-control">
</div>
</div>

<div class="form-check mb-3">
<input type="checkbox" class="form-check-input" onclick="togglePassword()">
<label class="form-check-label">Show Password</label>
</div>

<hr>

<h6>Module Access</h6>

<div class="row">

<?php
$triggerKeysJS = ['student_visa','work_visa','visitor_visa','pr_application','loan_module'];
$dependentKeysJS = ['lead_management','lead_source_tracking','calling_followup','staff_report'];

while ($module = $modulesResult->fetch_assoc()):
?>
<div class="col-md-4 mb-2">
<div class="form-check">
<input class="form-check-input module-checkbox"
       type="checkbox"
       name="modules[]"
       value="<?= $module['id'] ?>"
       data-key="<?= $module['module_key'] ?>"
       <?= in_array($module['id'], $assigned) ? 'checked' : '' ?>>
<label class="form-check-label">
<?= htmlspecialchars($module['module_name']) ?>
</label>
</div>
</div>
<?php endwhile; ?>

</div>

<div class="text-end mt-4">
<button type="submit" class="btn btn-dark">Update Employee</button>
<a href="employee-list.php" class="btn btn-secondary">Back</a>
</div>

</form>

</div>
</div>

<script>
function togglePassword() {
    const field = document.getElementById("passwordField");
    field.type = field.type === "password" ? "text" : "password";
}

/* Dependency Logic (Frontend) */

document.addEventListener("DOMContentLoaded", function () {

    const triggerKeys = <?= json_encode($triggerKeysJS) ?>;
    const dependentKeys = <?= json_encode($dependentKeysJS) ?>;

    const checkboxes = document.querySelectorAll(".module-checkbox");

    function updateDependencies() {

        let anyTriggerSelected = false;

        checkboxes.forEach(cb => {
            if (triggerKeys.includes(cb.dataset.key) && cb.checked) {
                anyTriggerSelected = true;
            }
        });

        checkboxes.forEach(cb => {
            if (dependentKeys.includes(cb.dataset.key)) {
                if (anyTriggerSelected) {
                    cb.checked = true;
                    cb.disabled = true;
                } else {
                    cb.disabled = false;
                }
            }
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener("change", updateDependencies);
    });

    updateDependencies();
});
</script>

</body>
</html>