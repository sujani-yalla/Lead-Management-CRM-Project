<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('lead_management')) {
    http_response_code(403);
    die("Access Denied");
}

/* SECURITY CHECK */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

/* VALIDATE ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Lead ID.");
}

$lead_id = (int) $_GET['id'];

/* FETCH LEAD WITH STAFF + CAMPAIGN */
$stmt = $conn->prepare("
    SELECT l.*, 
           u.name AS staff_name,
           c.campaign_name
    FROM leads l
    LEFT JOIN users u ON u.id = l.assigned_to
    LEFT JOIN campaigns c ON c.id = l.campaign_id
    WHERE l.id = ?
");

$stmt->bind_param("i", $lead_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Lead not found.");
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lead Details</title>

    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="container-fluid mt-4">

    <!-- HEADER SECTION -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Lead Details</h4>

        <a href="lead-list.php"
           class="btn btn-outline-secondary btn-sm rounded-2">
            ← Back to List
        </a>
    </div>

    <div class="row">

        <!-- LEAD INFORMATION -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Lead Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($row['lead_name']) ?></p>
                    <p><strong>Mobile:</strong> <?= htmlspecialchars($row['mobile']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                    <p><strong>Address:</strong><br>
                        <?= nl2br(htmlspecialchars($row['address'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- CLASSIFICATION -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Lead Classification</h5>
                </div>
                <div class="card-body">
                    <p><strong>Lead Source:</strong> <?= htmlspecialchars($row['lead_source']) ?></p>
                    <p><strong>Campaign:</strong> <?= $row['campaign_name'] ?? '—' ?></p>
                    <p><strong>Lead Type:</strong> <?= htmlspecialchars($row['lead_type']) ?></p>
                    <p><strong>Assigned Staff:</strong> <?= htmlspecialchars($row['staff_name']) ?></p>
                    <p><strong>Created On:</strong> 
                        <?= date("d M Y", strtotime($row['created_at'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- REFERENCE -->
        <?php if (!empty($row['reference_name']) || !empty($row['reference_contact'])): ?>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Reference Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Reference Name:</strong> 
                        <?= $row['reference_name'] ?? '—' ?>
                    </p>
                    <p><strong>Reference Contact:</strong> 
                        <?= $row['reference_contact'] ?? '—' ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- COMMENTS -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Comments</h5>
                </div>
                <div class="card-body">
                    <p><strong>Comment:</strong><br>
                        <?= nl2br(htmlspecialchars($row['comment'] ?? '—')) ?>
                    </p>
                    <hr>
                    <p><strong>Re-Comment:</strong><br>
                        <?= nl2br(htmlspecialchars($row['re_comment'] ?? '—')) ?>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>

</body>
</html>