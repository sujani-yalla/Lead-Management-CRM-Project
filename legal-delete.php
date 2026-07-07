<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('ca_legal')) {
    http_response_code(403);
    die("Access Denied");
}

/* ================= LOGIN CHECK ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

/* ================= ROLE SECURITY ================= */
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized Access");
}

/* ================= DELETE LOGIC ================= */
if (isset($_POST['id'])) {

    $id = intval($_POST['id']); // safe numeric conversion

    $delete = mysqli_query($conn, "
        DELETE FROM legal_cases 
        WHERE id = '$id'
    ");

    if ($delete) {
        header("Location: legal-list.php?deleted=1");
        exit();
    } else {
        echo "Error deleting record.";
    }

} else {
    header("Location: legal-list.php");
    exit();
}
?>