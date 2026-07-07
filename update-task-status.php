<?php
session_start();
include "db.php";

/* Allow only logged-in employees */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['status'])) {

    $taskId = intval($_GET['id']);
    $status = $_GET['status'];
    $userId = $_SESSION['user_id'];

    /* Allowed statuses */
    $allowedStatuses = ['In Progress', 'Need Support'];

    if (!in_array($status, $allowedStatuses)) {
        die("Invalid status");
    }

    /* Update only if task belongs to this employee */
    $stmt = $conn->prepare("
        UPDATE tasks 
        SET status = ? 
        WHERE id = ? AND assigned_to = ?
    ");

    $stmt->bind_param("sii", $status, $taskId, $userId);
    $stmt->execute();
    $stmt->close();
}

/* Redirect back */
header("Location: my-tasks.php");
exit;
?>
