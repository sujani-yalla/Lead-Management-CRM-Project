<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    require_once "db.php";
}

function hasAccess($module_key) {

    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        return false;
    }

    if ($_SESSION['role'] === 'admin') {
        return true;
    }

    global $conn;

    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT 1
        FROM employee_module_access ema
        JOIN modules m ON ema.module_id = m.id
        WHERE ema.user_id = ? AND m.module_key = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("is", $userId, $module_key);
    $stmt->execute();
    $stmt->store_result();

    return $stmt->num_rows > 0;
}