<?php
session_start();
require 'db.php';

/* Admin Only */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* Only allow POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: employee-list.php");
    exit;
}

if (!isset($_POST['user_id'])) {
    header("Location: employee-list.php");
    exit;
}

$user_id = intval($_POST['user_id']);

/* Prevent admin from deleting themselves */
if ($user_id === $_SESSION['user_id']) {
    header("Location: employee-list.php?error=You cannot delete your own account.");
    exit;
}

/* Check if employee exists */
$checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'employee'");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: employee-list.php?error=Employee not found.");
    exit;
}

/* Delete module access first */
$deleteAccessStmt = $conn->prepare("DELETE FROM employee_module_access WHERE user_id = ?");
$deleteAccessStmt->bind_param("i", $user_id);
$deleteAccessStmt->execute();

/* Delete user */
$deleteUserStmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
$deleteUserStmt->bind_param("i", $user_id);
$deleteUserStmt->execute();

header("Location: employee-list.php?success=Employee deleted successfully.");
exit;
?>