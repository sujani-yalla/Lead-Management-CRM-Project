<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

// Role-based query
if ($role === 'admin') {
    $query = "
        SELECT tr.*, t.task_code, u.name AS employee_name
        FROM task_reports tr
        JOIN tasks t ON tr.task_id = t.id
        JOIN users u ON t.assigned_to = u.id
        ORDER BY tr.submitted_at DESC
    ";
} else {
    $query = "
        SELECT tr.*, t.task_code, u.name AS employee_name
        FROM task_reports tr
        JOIN tasks t ON tr.task_id = t.id
        JOIN users u ON t.assigned_to = u.id
        WHERE u.id = $user_id
        ORDER BY tr.submitted_at DESC
    ";
}

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task Reports</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">

    <style>
        body { background:#f5f7fa; }
        .card { border-radius:10px; }
        .status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-approved {
    background: #1e7e34;
    color: white;
}

.badge-rejected {
    background: #b02a37;
    color: white;
}
.badge-waiting {
    background: #6c757d;
    color: #ffffff;
}
        
        .table th { font-weight:600; font-size:14px; }
        .table td {
    padding-top: 14px;
    padding-bottom: 14px;
}
    </style>
</head>

<body>
<div class="container mt-4">

    <a href="dashboard.php" class="btn btn-light border mb-3">
        ⬅ Back to Dashboard
    </a>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h6 class="mb-0">Task Reports</h6>
        </div>

        <div class="card-body">

            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Task Code</th>
                        <?php if($role === 'admin'): ?>
                            <th>Employee</th>
                        <?php endif; ?>
                        <th>Submitted On</th>
                        <th>Time Spent</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>

                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>

                    <tr>
                        <td><?= htmlspecialchars($row['task_code']) ?></td>

                        <?php if($role === 'admin'): ?>
                            <td><?= htmlspecialchars($row['employee_name']) ?></td>
                        <?php endif; ?>

                        <td><?= date("d M Y, h:i A", strtotime($row['submitted_at'])) ?></td>

                        <td><?= rtrim(rtrim($row['time_taken'], '0'), '.') ?> hrs</td>

                        <td>
                            <?php
                                $status = $row['review_status'];
                                $class = $status === 'Approved' ? 'badge-approved' :
                                         ($status === 'Rejected' ? 'badge-rejected' : 'badge-waiting');
                            ?>
                            <span class="status-badge text-white <?= $class ?>">
                                <?= $status ?>
                            </span>
                        </td>

                        <td>
                            <a href="view-task-report.php?id=<?= $row['id'] ?>"
                              class="btn btn-sm btn-light border text-primary">
                                View Details
                            </a>
                        </td>
                    </tr>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No reports found</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>
</div>
</body>
</html>