<?php
session_start();
require 'db.php';
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('work_visa')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['id'])) {
    header("Location: work-visa-list.php");
    exit;
}

$id = intval($_POST['id']);
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* VERIFY RECORD */

if ($role === 'admin') {
    $check = $conn->prepare("SELECT id FROM work_visas WHERE id = ?");
    $check->bind_param("i", $id);
} else {
    $check = $conn->prepare("SELECT id FROM work_visas WHERE id = ? AND assigned_to = ?");
    $check->bind_param("ii", $id, $userId);
}

$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    header("Location: work-visa-list.php");
    exit;
}

$check->close();

/* DELETE CHILD TABLES */

$del1 = $conn->prepare("DELETE FROM work_visa_marketing WHERE visa_id = ?");
$del1->bind_param("i", $id);
$del1->execute();
$del1->close();

$del2 = $conn->prepare("DELETE FROM work_visa_direct WHERE visa_id = ?");
$del2->bind_param("i", $id);
$del2->execute();
$del2->close();

/* DELETE PARENT */

$delParent = $conn->prepare("DELETE FROM work_visas WHERE id = ?");
$delParent->bind_param("i", $id);
$delParent->execute();
$delParent->close();

header("Location: work-visa-list.php?deleted=1");
exit;
