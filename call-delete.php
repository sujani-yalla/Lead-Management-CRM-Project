<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('call_followup')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Request");
}

if (!isset($_POST['id'], $_POST['lead_id'])) {
    die("Invalid Data");
}

$call_id = intval($_POST['id']);
$lead_id = intval($_POST['lead_id']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* Fetch call record */
$stmt = $conn->prepare("SELECT user_id FROM call_logs WHERE id = ?");
$stmt->bind_param("i", $call_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Record Not Found");
}

$call = $result->fetch_assoc();

/* Permission check */
if ($role !== 'admin' && $call['user_id'] != $user_id) {
    die("Unauthorized Action");
}

/* Delete */
$delete = $conn->prepare("DELETE FROM call_logs WHERE id = ?");
$delete->bind_param("i", $call_id);
$delete->execute();

header("Location: call-history.php?lead_id=" . $lead_id);
exit;