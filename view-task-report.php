<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$report_id = $_GET['id'] ?? 0;

$query = "
    SELECT tr.*, t.task_code, u.name AS employee_name
    FROM task_reports tr
    JOIN tasks t ON tr.task_id = t.id
    JOIN users u ON t.assigned_to = u.id
    WHERE tr.id = $report_id
";

$result = $conn->query($query);
$report = $result->fetch_assoc();

if (!$report) {
    die("Report not found");
}

// Restrict employee access
if ($role !== 'admin' && $report['employee_name'] != $_SESSION['name']) {
    die("Access denied");
}

// Fetch files
$fileQuery = $conn->query("
    SELECT * FROM task_report_files
    WHERE report_id = $report_id
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Task Report</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
    <style>
        body { background:#f5f7fa; }
        .card { border-radius:10px; }
        .status { padding:6px 14px; border-radius:20px; font-size:13px; }
        .approved { background:#198754; color:white; }
        .rejected { background:#dc3545; color:white; }
        .waiting { background:#6c757d; color:white; }
    </style>
</head>

<body>
<div class="container mt-4">

    <a href="task-reports.php" class="btn btn-light border mb-3">
        ⬅ Back to Reports
    </a>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h6 class="mb-0">Task Report Details</h6>
        </div>

        <div class="card-body">

            <p><strong>Task Code:</strong> <?= $report['task_code'] ?></p>
            <p><strong>Employee:</strong> <?= $report['employee_name'] ?></p>
            <p><strong>Submitted:</strong> <?= date("d M Y, h:i A", strtotime($report['submitted_at'])) ?></p>
            <p><strong>Time Spent:</strong> <?= $report['time_taken'] ?> hours</p>

            <p>
                <strong>Status:</strong>
                <?php
                    $statusClass = strtolower($report['review_status']);
                ?>
                <span class="status <?= $statusClass ?>">
                    <?= $report['review_status'] ?>
                </span>
            </p>

            <?php if($report['review_status'] === 'Rejected'): ?>
                <p><strong>Rejection Reason:</strong> <?= $report['review_remark'] ?></p>
            <?php endif; ?>

            <hr>

            <h6>Work Description</h6>
            <p><?= nl2br(htmlspecialchars($report['work_description'])) ?></p>

            <hr>

            <h6>Proof Files</h6>

            <?php if($fileQuery->num_rows > 0): ?>
                <?php while($file = $fileQuery->fetch_assoc()): ?>
                    <div class="mb-2">
                        <a href="<?= $file['file_path'] ?>" target="_blank"
                           class="btn btn-outline-primary btn-sm">
                            View
                        </a>

                        <a href="<?= $file['file_path'] ?>" download
                           class="btn btn-outline-secondary btn-sm">
                            Download
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No files uploaded</p>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>