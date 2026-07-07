<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('lead_source_tracking')) {
    http_response_code(403);
    die("Access Denied");
}

/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access Denied. Only admin can delete campaigns.");
}

if (!isset($_GET['id'])) {
    die("Invalid Campaign ID");
}

$campaignId = intval($_GET['id']);

/* ===== CHECK IF LEADS EXIST ===== */
$check = $conn->prepare("SELECT COUNT(*) FROM leads WHERE campaign_id = ?");
$check->bind_param("i", $campaignId);
$check->execute();
$check->bind_result($leadCount);
$check->fetch();
$check->close();

if ($leadCount > 0) {
    die("Cannot delete campaign. Leads are associated with this campaign.");
}

/* ===== DELETE ===== */
$delete = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
$delete->bind_param("i", $campaignId);

if ($delete->execute()) {
    header("Location: campaign-list.php?deleted=1");
    exit;
} else {
    die("Delete failed.");
}