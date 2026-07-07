<?php
session_start();
include "db.php";

/* HARD STOP if not admin */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin only.");
}

/* Generate Task Code */
function generateTaskCode($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM tasks");
    $row = $result->fetch_assoc();
    $next = $row['total'] + 1;
    return "IOS-TASK-" . str_pad($next, 4, "0", STR_PAD_LEFT);
}

/* INSERT LOGIC */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $task_code   = generateTaskCode($conn);
    $assign_date = $_POST['assign_date'];
    $assigned_to = $_POST['assigned_to'];
    $title       = $_POST['title'];
    $description = $_POST['description'];
    $priority    = $_POST['priority'];
    $deadline    = $_POST['deadline'];
    $department  = $_POST['department'];
    $created_by  = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        INSERT INTO tasks
        (task_code, assign_date, assigned_to, title, description, priority, deadline, department, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssisssssi",
        $task_code,
        $assign_date,
        $assigned_to,
        $title,
        $description,
        $priority,
        $deadline,
        $department,
        $created_by
    );

    if ($stmt->execute()) {
        header("Location: assign-task.php?success=1");
        exit();
    } else {
        die($stmt->error);
    }
}

$employees = $conn->query("SELECT id, name FROM users WHERE role='employee'");
?>



<!DOCTYPE html>
<html>
<head>
    <title>Assign Task</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>

    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        .form-container {
            max-width: 950px;
            margin: 50px auto;
        }

        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 14px 30px rgba(0,0,0,0.06);
        }

        .card-header {
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            font-size: 18px;
        }

        .btn-custom {
            padding: 10px 28px;
            font-weight: 600;
            border-radius: 8px;
        }

        .form-label {
            font-weight: 500;
            font-size: 14px;
        }

        .priority-high { color: #dc3545; font-weight: 600; }
        .priority-medium { color: #fd7e14; font-weight: 600; }
        .priority-low { color: #198754; font-weight: 600; }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container form-container">

    <div class="header-bar">
        <h4 class="fw-bold mb-0">Task Assignment Panel</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            ← Back to Dashboard
        </a>
    </div>

    <div class="card">
        <div class="card-body p-4">

            <form method="POST">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Assign Date</label>
                        <input type="date" name="assign_date" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Staff Name</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">Select Staff</option>
                            <?php
                            $employees = $conn->query("SELECT id, name FROM users WHERE role='employee'");
                            while ($emp = $employees->fetch_assoc()):
                            ?>
                                <option value="<?= $emp['id'] ?>">
                                    <?= htmlspecialchars($emp['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Task Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Task Description</label>
                    <textarea name="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option class="priority-high">High</option>
                            <option class="priority-medium">Medium</option>
                            <option class="priority-low">Low</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Deadline</label>
                        <input type="datetime-local" name="deadline" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control">
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-custom">
                        Assign Task
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<?php if (isset($_GET['success'])): ?>
<div class="modal fade show" id="successModal" tabindex="-1" style="display:block; background:rgba(0,0,0,0.4);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <h5 class="text-success fw-bold">Task Assigned Successfully ✅</h5>
                <p class="text-muted mt-2">The task has been assigned to the selected staff member.</p>
                <div class="mt-3">
                    <a href="assign-task.php" class="btn btn-outline-primary btn-sm me-2">
                        Assign Another
                    </a>
                    <a href="dashboard.php" class="btn btn-primary btn-sm">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>