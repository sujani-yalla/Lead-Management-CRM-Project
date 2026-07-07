<?php
session_start();
require 'db.php';
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('social_media')) {
    http_response_code(403);
    die("Access Denied");
}

/* ================= SESSION CHECK ================= */

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Request");
}

if (!isset($_POST['id'])) {
    die("Invalid Access");
}

$id      = intval($_POST['id']);
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

/* ================= ROLE BASED DELETE ================= */

if ($role === 'admin') {

    // Admin can delete any record
    $stmt = $conn->prepare("
        DELETE FROM social_posts
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);

} else {

    // Employee can delete ONLY their own post
    $stmt = $conn->prepare("
        DELETE FROM social_posts
        WHERE id = ? AND created_by = ?
    ");
    $stmt->bind_param("ii", $id, $user_id);
}

$stmt->execute();

/* ================= CHECK IF ACTUALLY DELETED ================= */

if ($stmt->affected_rows > 0) {
    header("Location: social-list.php?deleted=1");
    exit;
} else {
    die("Unauthorized or Record Not Found");
}