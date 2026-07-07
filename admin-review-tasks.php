<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$query = "
SELECT tr.*, t.task_code, t.title, u.name as employee_name
FROM task_reports tr
JOIN tasks t ON tr.task_id = t.id
JOIN users u ON t.assigned_to = u.id
WHERE tr.review_status = 'Waiting'
ORDER BY tr.submitted_at DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Task Reports</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">

    <style>
      body {
    background: #f5f7fa;
    font-family: "Segoe UI", Arial, sans-serif;
}

.card {
    border-radius: 12px;
    border: none;
}

.card-header {
    background: #1f2937;
    color: #ffffff;
    padding: 16px 22px;
    border-radius: 12px 12px 0 0;
    font-weight: 500;
}

.task-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 18px;
    background: #ffffff;
}

.task-title {
    font-weight: 600;
    font-size: 15px;
    color: #111827;
}

.employee-name {
    font-size: 13px;
    color: #6b7280;
}

.meta-info {
    font-size: 13px;
    color: #6b7280;
}

.description-box {
    background: #f9fafb;
    padding: 14px;
    border-radius: 8px;
    margin-top: 12px;
    border: 1px solid #f1f5f9;
    font-size: 14px;
    line-height: 1.6;
}

.btn-approve {
    background: #16a34a;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 6px 18px;
    font-size: 14px;
    font-weight: 500;
}

.btn-approve:hover {
    background: #15803d;
}

.btn-reject {
    background: #dc2626;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 6px 18px;
    font-size: 14px;
    font-weight: 500;
}

.btn-reject:hover {
    background: #b91c1c;
}

.file-button {
    border: 1px solid #d1d5db;
    background: #ffffff;
    padding: 4px 10px;
    font-size: 13px;
    border-radius: 6px;
    margin-right: 6px;
}

.file-button:hover {
    background: #f3f4f6;
}
    </style>
</head>

<body>

<div class="container mt-4">

    <a href="dashboard.php" class="btn btn-light mb-3 shadow-sm rounded-pill px-3">
        ⬅ Back to Dashboard
    </a>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white rounded-top">
            <h5 class="mb-0">📋 Task Reports Waiting for Review</h5>
        </div>

        <div class="card-body">

            <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>

                <div class="task-card">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?= htmlspecialchars($row['task_code']) ?></strong>
                            <br>
                            <small class="text-muted">
                                <?= htmlspecialchars($row['employee_name']) ?>
                            </small>
                        </div>

                       <div class="meta-info">
                             Submitted: <?= date("d M Y, h:i A", strtotime($row['submitted_at'])) ?>
                        • Time Spent: <?= intval($row['time_taken']) ?> hours
                       </div>
                    </div>

                    <div class="mb-3 description-box">
                        <?= nl2br(htmlspecialchars($row['work_description'])) ?>
                    </div>

                    <div class="mb-3 proof-box">
                        <strong>Proof Files:</strong><br>

                        <?php
                        $files = $conn->query("
                            SELECT * FROM task_report_files 
                            WHERE report_id = {$row['id']}
                        ");

                        if ($files->num_rows > 0):
                            while($file = $files->fetch_assoc()):
                        ?>
                            <a href="<?= $file['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                👁 View
                            </a>

                            <a href="<?= $file['file_path'] ?>" download class="btn btn-sm btn-outline-secondary">
                                ⬇ Download
                            </a>
                        <?php
                            endwhile;
                        else:
                            echo "<span class='text-muted'>No files uploaded</span>";
                        endif;
                        ?>
                    </div>

                    <div class="d-flex gap-2">

                        <form method="POST" action="review-action.php">
                            <input type="hidden" name="report_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve text-white">
                                ✔ Approve
                            </button>
                        </form>

                        <button 
                            class="btn btn-reject text-white"
                            data-bs-toggle="modal"
                            data-bs-target="#rejectModal<?= $row['id'] ?>">
                            ✖ Reject
                        </button>

                    </div>

                </div>

                <!-- Reject Modal -->
                <div class="modal fade" id="rejectModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="review-action.php">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Reject Task</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="report_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <textarea 
                                        name="rejection_reason" 
                                        class="form-control"
                                        rows="4"
                                        required
                                        placeholder="Enter rejection reason..."></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-danger">
                                        Confirm Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted">
                    No reports waiting for review.
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>