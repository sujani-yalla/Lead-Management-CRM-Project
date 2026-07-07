<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$report_id = $_POST['report_id'] ?? 0;
$action = $_POST['action'] ?? '';
$rejection_reason = $_POST['rejection_reason'] ?? null;

if (!$report_id || !in_array($action, ['approve','reject'])) {
    die("Invalid request");
}

/* Get task_id first */
$stmt = $conn->prepare("SELECT task_id FROM task_reports WHERE id=?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$stmt->bind_result($task_id);
$stmt->fetch();
$stmt->close();

if (!$task_id) {
    die("Report not found");
}

if ($action === 'approve') {

    $stmt = $conn->prepare("
        UPDATE task_reports 
        SET review_status='Approved',
            reviewed_by=?,
            reviewed_at=NOW(),
            employee_seen=0
        WHERE id=?
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $report_id);
    $stmt->execute();
    $stmt->close();

    $update = $conn->prepare("UPDATE tasks SET status='Completed' WHERE id=?");
    $update->bind_param("i", $task_id);
    $update->execute();
    $update->close();
}

if ($action === 'reject') {

    if (empty($rejection_reason)) {
        die("Rejection reason required");
    }

    $stmt = $conn->prepare("
        UPDATE task_reports 
        SET review_status='Rejected',
            reviewed_by=?,
            reviewed_at=NOW(),
            rejection_reason=?,
            employee_seen=0
        WHERE id=?
    ");
    $stmt->bind_param("isi", $_SESSION['user_id'], $rejection_reason, $report_id);
    $stmt->execute();
    $stmt->close();

    $update = $conn->prepare("UPDATE tasks SET status='Rejected' WHERE id=?");
    $update->bind_param("i", $task_id);
    $update->execute();
    $update->close();
}


header("Location: admin-review-tasks.php");
exit;
?>
