<?php
session_start();
require 'db.php';
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('student_visa')) {
    http_response_code(403);
    die("Access Denied");
}

/* ===== SECURITY CHECK ===== */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

/* ===== ONLY ALLOW POST ===== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_POST['id'])) {
    die("Invalid request.");
}

$id     = intval($_POST['id']);
$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];

/* ===== START TRANSACTION ===== */
$conn->begin_transaction();

try {

    /* ===== VERIFY OWNERSHIP (FOR EMPLOYEE) ===== */
    if ($role !== 'admin') {

        $check = $conn->prepare("
            SELECT id FROM visas
            WHERE id = ?
            AND visa_type = 'student'
            AND assigned_to = ?
        ");
        $check->bind_param("ii", $id, $userId);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            throw new Exception("Unauthorized action.");
        }

        $check->close();
    }

    /* ===== DELETE STUDENT DETAILS FIRST ===== */
    $deleteDetails = $conn->prepare("
        DELETE FROM student_visa_details
        WHERE visa_id = ?
    ");
    $deleteDetails->bind_param("i", $id);
    $deleteDetails->execute();
    $deleteDetails->close();

    /* ===== DELETE MAIN VISA RECORD ===== */
    if ($role === 'admin') {

        $deleteVisa = $conn->prepare("
            DELETE FROM visas
            WHERE id = ?
            AND visa_type = 'student'
        ");
        $deleteVisa->bind_param("i", $id);

    } else {

        $deleteVisa = $conn->prepare("
            DELETE FROM visas
            WHERE id = ?
            AND visa_type = 'student'
            AND assigned_to = ?
        ");
        $deleteVisa->bind_param("ii", $id, $userId);
    }

    $deleteVisa->execute();

    if ($deleteVisa->affected_rows === 0) {
        throw new Exception("Record not found.");
    }

    $deleteVisa->close();

    /* ===== COMMIT ===== */
    $conn->commit();

} catch (Exception $e) {

    $conn->rollback();
    die("Delete failed: " . $e->getMessage());
}

header("Location: student-visa-list.php");
exit;
?>