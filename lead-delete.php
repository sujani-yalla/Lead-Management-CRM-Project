<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('lead_management')) {
    http_response_code(403);
    die("Access Denied");
}

/* SECURITY CHECK */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

/* ONLY ADMIN CAN DELETE */
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized Access.");
}

/* POST ONLY */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("Invalid ID.");
}

$id = (int) $_POST['id'];

/* DELETE */
$stmt = $conn->prepare("DELETE FROM leads WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: lead-list.php?deleted=1");
    exit;
} else {
    die("Record not found.");
}