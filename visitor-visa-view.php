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

if (!isset($_GET['id'])) {
    echo "Invalid Access";
    exit;
}

$visaId = intval($_GET['id']);

if ($_SESSION['role'] === 'admin') {

    $stmt = $conn->prepare("
        SELECT 
            v.*,
            l.lead_name,
            l.mobile,
            l.email,
            l.lead_source,
            u.name AS employee_name,
            cu.name AS created_by_name
        FROM visas v
        JOIN leads l ON l.id = v.lead_id
        LEFT JOIN users u ON u.id = l.assigned_to
        LEFT JOIN users cu ON cu.id = v.created_by
        WHERE v.id = ? AND v.visa_type = 'visitor'
    ");

    $stmt->bind_param("i", $visaId);

} else {

    $stmt = $conn->prepare("
        SELECT 
            v.*,
            l.lead_name,
            l.mobile,
            l.email,
            l.lead_source,
            u.name AS employee_name,
            cu.name AS created_by_name
        FROM visas v
        JOIN leads l ON l.id = v.lead_id
        LEFT JOIN users u ON u.id = l.assigned_to
        LEFT JOIN users cu ON cu.id = v.created_by
        WHERE v.id = ? 
        AND v.visa_type = 'visitor'
        AND l.assigned_to = ?
    ");

    $stmt->bind_param("ii", $visaId, $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "Record not found";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visitor Case Details</title>
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>

<body>

<div class="container-fluid mt-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Visitor Visa Case Details</h4>
            <small class="text-muted">
                Case ID: VV<?= str_pad($data['id'], 3, "0", STR_PAD_LEFT) ?>
            </small>
        </div>
        <a href="visitor-visa-list.php" class="btn btn-outline-secondary btn-sm">
            ← Back to List
        </a>
    </div>

    <div class="row g-4">

        <!-- Lead Information Card -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-primary">Lead Information</h6>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($data['lead_name']) ?></p>
                    <p><strong>Mobile:</strong> <?= htmlspecialchars($data['mobile']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($data['email']) ?></p>
                    <p><strong>Lead Source:</strong> <?= htmlspecialchars($data['lead_source']) ?></p>
                    <p><strong>Assigned Employee:</strong> <?= htmlspecialchars($data['employee_name']) ?></p>
                </div>
            </div>
        </div>

        <!-- Travel Details Card -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-primary">Travel & Visa Details</h6>
                </div>
                <div class="card-body">
                    <p><strong>Applying Country:</strong> <?= htmlspecialchars($data['country']) ?></p>
                    <p><strong>Purpose of Visit:</strong> <?= htmlspecialchars($data['purpose_of_visit']) ?></p>
                    <p><strong>Processing Start Date:</strong> 
                        <?= $data['processing_start_date'] ? date("d-m-Y", strtotime($data['processing_start_date'])) : '-' ?>
                    </p>
                    <p><strong>Travel Date:</strong> 
                        <?= $data['travel_date'] ? date("d-m-Y", strtotime($data['travel_date'])) : '-' ?>
                    </p>
                    <p><strong>Travel Duration:</strong> <?= $data['travel_duration'] ?> Days</p>
                </div>
            </div>
        </div>

        <!-- Passport & History -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-primary">Passport & Travel History</h6>
                </div>
                <div class="card-body">
                    <p><strong>Passport Number:</strong> <?= htmlspecialchars($data['passport_number']) ?></p>
                    <p>
                        <strong>Previous Visa Refusal:</strong>
                        <?php if ($data['previous_visa_refusal'] === 'Yes') { ?>
                            <span class="badge bg-danger">Yes</span>
                        <?php } else { ?>
                            <span class="badge bg-success">No</span>
                        <?php } ?>
                    </p>
                    <p><strong>Previous Visit Countries:</strong><br>
                        <?= nl2br(htmlspecialchars($data['previous_visit_countries'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Status Tracking -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-primary">Status & Notes</h6>
                </div>
                <div class="card-body">

                    <p>
                        <strong>Application Status:</strong>
                        <?php
                        switch($data['status']) {
                            case 'approved':
                                echo '<span class="badge bg-success">Approved</span>';
                                break;
                            case 'submitted':
                                echo '<span class="badge bg-info">Submitted</span>';
                                break;
                            case 'rejected':
                                echo '<span class="badge bg-danger">Rejected</span>';
                                break;
                            default:
                                echo '<span class="badge bg-warning">Pending</span>';
                        }
                        ?>
                    </p>

                    <p>
                        <strong>Visa Status:</strong>
                        <?php
                        switch($data['visa_status']) {
                            case 'approved':
                                echo '<span class="badge bg-success">Approved</span>';
                                break;
                            case 'processing':
                                echo '<span class="badge bg-info">Processing</span>';
                                break;
                            case 'rejected':
                                echo '<span class="badge bg-danger">Rejected</span>';
                                break;
                            default:
                                echo '<span class="badge bg-warning">On Hold</span>';
                        }
                        ?>
                    </p>

                    <p><strong>Pending Documents:</strong><br>
                        <?= nl2br(htmlspecialchars($data['pending_documents'])) ?>
                    </p>

                    <p><strong>Notes:</strong><br>
                        <?= nl2br(htmlspecialchars($data['notes'])) ?>
                    </p>

                    <hr>

                    <small class="text-muted">
                        Created By: <?= htmlspecialchars($data['created_by_name']) ?><br>
                        Created At: <?= date("d-m-Y H:i", strtotime($data['created_at'])) ?>
                    </small>

                </div>
            </div>
        </div>

    </div>
</div>

<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>

</body>
</html>