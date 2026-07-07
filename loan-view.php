<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('loan_module')) {
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

$loanId = intval($_GET['id']);

if ($_SESSION['role'] === 'admin') {

    $stmt = $conn->prepare("
        SELECT 
            ln.*,
            l.lead_name,
            l.mobile,
            l.email,
            l.lead_source,
            u.name AS employee_name,
            cu.name AS created_by_name
        FROM loans ln
        JOIN leads l ON l.id = ln.lead_id
        LEFT JOIN users u ON u.id = l.assigned_to
        LEFT JOIN users cu ON cu.id = ln.created_by
        WHERE ln.id = ?
    ");

    $stmt->bind_param("i", $loanId);

} else {

    $stmt = $conn->prepare("
        SELECT 
            ln.*,
            l.lead_name,
            l.mobile,
            l.email,
            l.lead_source,
            u.name AS employee_name,
            cu.name AS created_by_name
        FROM loans ln
        JOIN leads l ON l.id = ln.lead_id
        LEFT JOIN users u ON u.id = l.assigned_to
        LEFT JOIN users cu ON cu.id = ln.created_by
        WHERE ln.id = ?
        AND l.assigned_to = ?
    ");

    $stmt->bind_param("ii", $loanId, $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "Record not found or access denied";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Case Details</title>
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>

<body>

<div class="container-fluid mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Loan Case Details</h4>
            <small class="text-muted">
                Case ID: LN<?= str_pad($data['id'], 3, "0", STR_PAD_LEFT) ?>
            </small>
        </div>
        <a href="loan-list.php" class="btn btn-outline-secondary btn-sm">
            ← Back to List
        </a>
    </div>

    <!-- ================= ROW 1 ================= -->
    <div class="row g-4">

        <!-- Lead Information -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
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

        <!-- Loan Details -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-primary">Loan Details</h6>
                </div>
                <div class="card-body">
                    <p><strong>Loan Start Date:</strong>
                        <?= $data['loan_start_date'] ? date("d-m-Y", strtotime($data['loan_start_date'])) : '-' ?>
                    </p>

                    <p><strong>Applied Banks:</strong><br>
                        <?= nl2br(htmlspecialchars($data['applied_banks'])) ?>
                    </p>

                    <p><strong>Approved Bank:</strong>
                        <?= htmlspecialchars($data['approved_bank_name']) ?>
                    </p>

                    <p><strong>Status:</strong>
                        <?php
                            $status = strtolower($data['loan_status']);
                            $badgeClass = 'bg-secondary';

                            if ($status === 'approved') $badgeClass = 'bg-success';
                            elseif ($status === 'rejected') $badgeClass = 'bg-danger';
                            elseif ($status === 'processing') $badgeClass = 'bg-warning';
                        ?>
                        <span class="badge <?= $badgeClass ?>">
                            <?= htmlspecialchars($data['loan_status']) ?>
                        </span>
                    </p>

                    <p><strong>Pending Documents:</strong><br>
                        <?= !empty($data['pending_documents']) 
                            ? nl2br(htmlspecialchars($data['pending_documents'])) 
                            : '-' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= ROW 2 ================= -->
    <div class="row g-4 mt-1">

        <!-- Sanction & Disbursement -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-primary">
                        Sanction & Disbursement
                    </h6>
                </div>
                <div class="card-body">

                    <p><strong>Sanctioned Date:</strong>
                        <?= $data['loan_sanctioned_date'] 
                            ? date("d-m-Y", strtotime($data['loan_sanctioned_date'])) 
                            : '-' ?>
                    </p>

                    <p><strong>Sanctioned Amount:</strong>
                        <?= $data['loan_sanctioned_amount'] !== null 
                            ? '₹' . number_format($data['loan_sanctioned_amount'], 2) 
                            : '-' ?>
                    </p>

                    <hr>

                    <p><strong>Disbursement Date:</strong>
                        <?= $data['loan_disbursement_date'] 
                            ? date("d-m-Y", strtotime($data['loan_disbursement_date'])) 
                            : '-' ?>
                    </p>

                    <p><strong>Disbursement Amount:</strong>
                        <?= $data['loan_disbursement_amount'] !== null 
                            ? '₹' . number_format($data['loan_disbursement_amount'], 2) 
                            : '-' ?>
                    </p>

                </div>
            </div>
        </div>
        

        <!-- CIBIL & Co-Applicants -->
        <div class="col-md-6">
    <div class="card shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold text-primary">
                CIBIL & Co-Applicants
            </h6>
        </div>
        <div class="card-body">

            <p><strong>Student CIBIL:</strong>
                <?= $data['student_cibil_score'] > 0 
                    ? htmlspecialchars($data['student_cibil_score']) 
                    : '-' ?>
            </p>

            <hr>
            <h6 class="fw-semibold">Co-Applicant 1</h6>
            <p><strong>Name:</strong>
                <?= !empty($data['co1_name']) ? htmlspecialchars($data['co1_name']) : '-' ?>
            </p>
            <p><strong>Relation:</strong>
                <?= !empty($data['co1_relation']) ? htmlspecialchars($data['co1_relation']) : '-' ?>
            </p>
            <p><strong>CIBIL:</strong>
                <?= $data['co1_cibil'] > 0 ? $data['co1_cibil'] : '-' ?>
            </p>

            <hr>
            <h6 class="fw-semibold">Co-Applicant 2</h6>
            <p><strong>Name:</strong>
                <?= !empty($data['co2_name']) ? htmlspecialchars($data['co2_name']) : '-' ?>
            </p>
            <p><strong>Relation:</strong>
                <?= !empty($data['co2_relation']) ? htmlspecialchars($data['co2_relation']) : '-' ?>
            </p>
            <p><strong>CIBIL:</strong>
                <?= $data['co2_cibil'] > 0 ? $data['co2_cibil'] : '-' ?>
            </p>

        </div>
    </div>
</div>

    <!-- ================= ROW 3 ================= -->
    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-primary">History & Comments</h6>
                </div>
                <div class="card-body">

                    <p>
                        <strong>Any Other Loans:</strong>
                        <span class="badge <?= $data['any_other_loans'] === 'Yes' ? 'bg-warning' : 'bg-success' ?>">
                            <?= htmlspecialchars($data['any_other_loans']) ?>
                        </span>
                    </p>

                    <?php if ($data['any_other_loans'] === 'Yes'): ?>
                        <p><strong>Other Loan Details:</strong><br>
                            <?= nl2br(htmlspecialchars($data['other_loan_details'])) ?>
                        </p>
                    <?php endif; ?>

                    <p>
                        <strong>Previous Rejections:</strong>
                        <span class="badge <?= $data['previous_rejections'] === 'Yes' ? 'bg-danger' : 'bg-success' ?>">
                            <?= htmlspecialchars($data['previous_rejections']) ?>
                        </span>
                    </p>

                    <?php if ($data['previous_rejections'] === 'Yes'): ?>
                        <p><strong>Rejection Details:</strong><br>
                            <?= nl2br(htmlspecialchars($data['rejection_details'])) ?>
                        </p>
                    <?php endif; ?>

                    <p><strong>Comments:</strong><br>
                        <?= !empty($data['comments']) 
                            ? nl2br(htmlspecialchars($data['comments'])) 
                            : '-' ?>
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