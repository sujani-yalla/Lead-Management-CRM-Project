<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('loan_module')) {
    http_response_code(403);
    die("Access Denied");
}

/* HARD STOP */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

/* Only allow POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid Request";
    exit;
}

/* Validate ID */
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo "Invalid Loan ID";
    exit;
}

$loanId = intval($_POST['id']);

/* ROLE-BASED SECURITY CHECK */
if ($_SESSION['role'] === 'admin') {

    // Admin can delete directly
    $stmt = $conn->prepare("DELETE FROM loans WHERE id = ?");
    $stmt->bind_param("i", $loanId);

} else {

    // Employee can delete only if loan belongs to their assigned lead
    $stmt = $conn->prepare("
        DELETE ln FROM loans ln
        JOIN leads l ON l.id = ln.lead_id
        WHERE ln.id = ? AND l.assigned_to = ?
    ");
    $stmt->bind_param("ii", $loanId, $_SESSION['user_id']);
}

$stmt->execute();

/* Optional: Check affected rows */
if ($stmt->affected_rows > 0) {
    header("Location: loan-list.php?msg=deleted");
    exit;
} else {
    echo "Delete failed or access denied";
    exit;
}
?>