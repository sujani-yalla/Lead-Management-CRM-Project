<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('staff_report')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

if (!isset($_POST['id'])) {
    die("Invalid Request");
}

$id = intval($_POST['id']);

// Optional: Verify record exists
$check = $conn->query("SELECT id FROM staff_work_reports WHERE id = $id");

if ($check->num_rows == 0) {
    die("Record not found");
}

// Delete
$conn->query("DELETE FROM staff_work_reports WHERE id = $id");

header("Location: staff-work-list.php");
exit;
?>