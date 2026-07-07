<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('visitor_visa')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_POST['id'])) {
    die("Invalid request.");
}

$id = intval($_POST['id']);
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* ================= DELETE WITH ROLE SECURITY ================= */

if ($role === 'admin') {

    $stmt = $conn->prepare("
        DELETE FROM visas
        WHERE id = ?
        AND visa_type = 'visitor'
    ");

    $stmt->bind_param("i", $id);

} else {

    $stmt = $conn->prepare("
        DELETE v FROM visas v
        JOIN leads l ON l.id = v.lead_id
        WHERE v.id = ?
        AND v.visa_type = 'visitor'
        AND l.assigned_to = ?
    ");

    $stmt->bind_param("ii", $id, $userId);
}

if (!$stmt->execute()) {
    die("Delete failed: " . $stmt->error);
}

if ($stmt->affected_rows === 0) {
    die("Unauthorized action or record not found.");
}

$stmt->close();

header("Location: visitor-visa-list.php?deleted=1");
exit;
?>