<?php
session_start();
include "db.php";

/* Allow only employees */
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* Fetch employee tasks with latest report */
$stmt = $conn->prepare("
    SELECT 
        t.id, 
        t.task_code, 
        t.title, 
        t.priority, 
        t.deadline, 
        t.status,
        tr.review_status,
        tr.rejection_reason
    FROM tasks t
    LEFT JOIN task_reports tr 
        ON tr.id = (
            SELECT id FROM task_reports 
            WHERE task_id = t.id 
            ORDER BY submitted_at DESC 
            LIMIT 1
        )
    WHERE t.assigned_to = ?
    ORDER BY t.deadline ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Tasks</title>
    <link href="assets/css/vendors/bootstrap.css" rel="stylesheet">
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>

    <style>
        body {
            background:#f4f6f9;
            font-family:'Segoe UI', sans-serif;
        }

        .page-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:30px;
        }

        .task-card {
            background:white;
            border-radius:18px;
            padding:28px;
            box-shadow:0 12px 28px rgba(0,0,0,0.05);
            margin-bottom:22px;
            transition:0.25s ease;
        }

        .task-card:hover {
            transform:translateY(-4px);
            box-shadow:0 18px 35px rgba(0,0,0,0.08);
        }

        .task-code {
            font-weight:700;
            font-size:18px;
        }

        .priority-high { color:#dc3545; font-weight:600; }
        .priority-medium { color:#fd7e14; font-weight:600; }
        .priority-low { color:#198754; font-weight:600; }

        .status-badge {
            padding:6px 14px;
            border-radius:30px;
            font-size:12px;
            font-weight:600;
            letter-spacing:0.5px;
        }

        .status-assigned { background:#e7f1ff; color:#0d6efd; }
        .status-progress { background:#fff4e5; color:#b45309; }
        .status-completed { background:#e6f9f0; color:#047857; }
        .status-rejected { background:#fde2e1; color:#b91c1c; }

        .task-info p {
            margin-bottom:6px;
        }

        .action-buttons .btn {
            border-radius:8px;
            padding:6px 14px;
            font-size:13px;
            font-weight:500;
        }

        .empty-state {
            background:white;
            border-radius:16px;
            padding:40px;
            text-align:center;
            box-shadow:0 10px 25px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>

<div class="container mt-5">

    <!-- HEADER -->
    <div class="page-header">
        <h4 class="fw-bold mb-0">My Assigned Tasks</h4>
        <a href="employee-dashboard.php" class="btn btn-outline-secondary btn-sm">
            ← Back to Dashboard
        </a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <?php while($task = $result->fetch_assoc()): ?>

            <?php
                $statusClass = '';
                if ($task['status'] == 'Assigned') $statusClass = 'status-assigned';
                elseif ($task['status'] == 'In Progress') $statusClass = 'status-progress';
                elseif ($task['status'] == 'Waiting Approval') $statusClass = 'status-progress';
                elseif ($task['status'] == 'Completed') $statusClass = 'status-completed';
                elseif ($task['status'] == 'Rejected') $statusClass = 'status-rejected';
            ?>

            <div class="task-card">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="task-code">
                        <?= htmlspecialchars($task['task_code']) ?>
                    </div>

                    <span class="status-badge <?= $statusClass ?>">
                        <?= htmlspecialchars($task['status']) ?>
                    </span>
                </div>

                <div class="task-info">

                    <p><strong>Title:</strong> <?= htmlspecialchars($task['title']) ?></p>

                    <p>
                        <strong>Priority:</strong>
                        <span class="priority-<?= strtolower($task['priority']) ?>">
                            <?= htmlspecialchars($task['priority']) ?>
                        </span>
                    </p>

                    <p>
                        <strong>Deadline:</strong>
                        <?= date("d M Y h:i A", strtotime($task['deadline'])) ?>
                    </p>

                </div>

                <?php if ($task['status'] == 'Rejected' && !empty($task['rejection_reason'])): ?>
                    <div class="mt-3 p-3 rounded" style="background:#fff5f5;">
                        <strong class="text-danger">Rejection Reason:</strong><br>
                        <?= htmlspecialchars($task['rejection_reason']) ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 action-buttons">

                    <?php if ($task['status'] == 'Assigned'): ?>
                        <a href="update-task-status.php?id=<?= $task['id'] ?>&status=In Progress"
                           class="btn btn-warning btn-sm">
                            Start Work
                        </a>
                    <?php endif; ?>

                    <?php if ($task['status'] == 'In Progress'): ?>
                        <a href="complete-task.php?id=<?= $task['id'] ?>"
                           class="btn btn-success btn-sm">
                            Mark Completed
                        </a>
                    <?php endif; ?>

                    <?php if ($task['status'] == 'Rejected'): ?>
                        <a href="complete-task.php?id=<?= $task['id'] ?>"
                           class="btn btn-danger btn-sm">
                            Re-Submit Task
                        </a>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty-state">
            <h6 class="text-muted">No tasks assigned yet</h6>
            <p class="text-muted">Your assigned tasks will appear here.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>