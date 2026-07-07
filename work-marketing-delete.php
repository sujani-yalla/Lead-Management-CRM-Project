<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['id'], $_POST['visa_id'])) {
    header("Location: work-visa-list.php");
    exit;
}

$id = intval($_POST['id']);
$visa_id = intval($_POST['visa_id']);

$stmt = $conn->prepare("DELETE FROM work_visa_marketing WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: work-visa-view.php?id=" . $visa_id);
exit;