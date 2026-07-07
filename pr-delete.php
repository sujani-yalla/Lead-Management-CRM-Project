<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('pr_application')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Invalid request method.");
}

if (!isset($_POST['id'])) {
    die("PR ID missing.");
}

$pr_id = intval($_POST['id']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* =========================
   CHECK PR EXISTS
========================= */
$stmt = $conn->prepare("
    SELECT lead_id, created_by 
    FROM pr_enquiries 
    WHERE id = ?
");
$stmt->bind_param("i", $pr_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid PR record.");
}

$data = $result->fetch_assoc();
$stmt->close();

/* =========================
   ROLE VALIDATION
========================= */
if ($role !== 'admin' && $data['created_by'] != $user_id) {
    http_response_code(403);
    die("Unauthorized action.");
}

/* =========================
   CHECK IF CASE EXISTS
========================= */
$checkCase = $conn->prepare("
    SELECT id FROM pr_case_details 
    WHERE lead_id = ?
");
$checkCase->bind_param("i", $data['lead_id']);
$checkCase->execute();
$checkCase->store_result();

if ($checkCase->num_rows > 0) {
    die("Cannot delete. PR case already initiated.");
}
$checkCase->close();

/* =========================
   DELETE PR ENQUIRY
========================= */
$delete = $conn->prepare("
    DELETE FROM pr_enquiries 
    WHERE id = ?
");
$delete->bind_param("i", $pr_id);
$delete->execute();

header("Location: pr-list.php");
exit;