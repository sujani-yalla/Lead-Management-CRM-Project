<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('pr_application')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Invalid PR ID");
}


$pr_id = intval($_GET['id']);

/* =========================
   FETCH PR DETAILS
========================= */

$stmt = $conn->prepare("
    SELECT pr.*, l.lead_name, l.mobile, l.email, u.name AS employee_name
    FROM pr_enquiries pr
    JOIN leads l ON l.id = pr.lead_id
    LEFT JOIN users u ON u.id = pr.created_by
    WHERE pr.id = ?
");
$stmt->bind_param("i", $pr_id);
$stmt->execute();
$pr = $stmt->get_result()->fetch_assoc();
function showField($label, $value) {
    echo '
    <div class="row mb-2">
        <div class="col-5 text-muted">'.$label.'</div>
        <div class="col-7 fw-semibold">'.($value !== null && $value !== '' ? htmlspecialchars($value) : '-').'</div>
    </div>';
}

if (!$pr) {
    die("PR record not found");
}

/* =========================
   PAYMENT SUMMARY
========================= */

$payStmt = $conn->prepare("
    SELECT SUM(amount) as total_paid
    FROM pr_payments
    WHERE lead_id = ?
");
$payStmt->bind_param("i", $pr['lead_id']);
$payStmt->execute();
$paymentData = $payStmt->get_result()->fetch_assoc();

$total_fee  = floatval($pr['total_fee'] ?? 0);
$total_paid = floatval($paymentData['total_paid'] ?? 0);
$balance    = $total_fee - $total_paid;
$hasPayment = $total_paid > 0;

/* =========================
   INSTALLMENT HISTORY
========================= */

$historyStmt = $conn->prepare("
    SELECT p.*, u.name AS added_by
    FROM pr_payments p
    LEFT JOIN users u ON u.id = p.created_by
    WHERE p.lead_id = ?
    ORDER BY p.id DESC
");
$historyStmt->bind_param("i", $pr['lead_id']);
$historyStmt->execute();
$paymentHistory = $historyStmt->get_result();

/* =========================
   CASE DETAILS
========================= */

$caseStmt = $conn->prepare("
    SELECT *
    FROM pr_case_details
    WHERE lead_id = ?
");
$caseStmt->bind_param("i", $pr['lead_id']);
$caseStmt->execute();
$case = $caseStmt->get_result()->fetch_assoc();

$case_id = $case['id'] ?? null;

$process = null;
$eduCount = 0;
$expCount = 0;
$docCount = 0;
$language = null;

if ($case_id) {

    // Process
    $processStmt = $conn->prepare("
        SELECT *
        FROM pr_case_process
        WHERE case_id = ?
    ");
    $processStmt->bind_param("i", $case_id);
    $processStmt->execute();
    $process = $processStmt->get_result()->fetch_assoc();

    // Education Count
    $eduStmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM pr_case_education
        WHERE case_id = ?
    ");
    $eduStmt->bind_param("i", $case_id);
    $eduStmt->execute();
    $eduCount = $eduStmt->get_result()->fetch_assoc()['total'];

    // Experience Count
    $expStmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM pr_case_experience
        WHERE case_id = ?
    ");
    $expStmt->bind_param("i", $case_id);
    $expStmt->execute();
    $expCount = $expStmt->get_result()->fetch_assoc()['total'];

    // Language
    $langStmt = $conn->prepare("
        SELECT *
        FROM pr_case_language
        WHERE case_id = ?
    ");
    $langStmt->bind_param("i", $case_id);
    $langStmt->execute();
    $language = $langStmt->get_result()->fetch_assoc();

    // Document Count
    $docStmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM pr_case_documents
        WHERE case_id = ?
    ");
    $docStmt->bind_param("i", $case_id);
    $docStmt->execute();
    $docCount = $docStmt->get_result()->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PR Case Details</title>
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <style>
.card .fw-bold {
    color: #212529 !important;
}
</style>
</head>

<body>

<div class="container-fluid">

<div class="row">
<div class="col-12">

<!-- PROFILE HEADER CARD -->
<div class="card mb-4">
<div class="card-body d-flex justify-content-between align-items-center">

<div>
    <h4 class="mb-1">PR<?= str_pad($pr['id'],3,'0',STR_PAD_LEFT) ?> - <?= htmlspecialchars($pr['lead_name']) ?></h4>
    <div class="text-muted">
        Target Country: <?= htmlspecialchars($pr['target_country'] ?? '-') ?> |
        Added By: <?= htmlspecialchars($pr['employee_name'] ?? 'N/A') ?> |
        Created: <?= date("d M Y", strtotime($pr['created_at'])) ?>
    </div>
</div>

<div>
    <a href="pr-list.php" class="btn btn-light border">
        <i data-feather="arrow-left"></i> Back to List
    </a>
</div>

</div>
</div>

</div>
</div>


<!-- 2 COLUMN DETAILS -->
<div class="row">

<div class="col-md-6">

<!-- BASIC DETAILS -->
<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">Basic Details</h6>
</div>
<div class="card-body">

<?php
showField("Lead Name", $pr['lead_name']);
showField("Mobile", $pr['mobile']);
showField("Email", $pr['email']);
showField("Current Country", $pr['current_country']);
showField("City", $pr['city']);
showField("Age", $pr['age']);
showField("Gender", $pr['gender']);
?>

</div>
</div>

<!-- PROFILE & EXPERIENCE -->
<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">Profile & Experience</h6>
</div>
<div class="card-body">

<?php
showField("Qualification", $pr['qualification']);
showField("Field of Study", $pr['field_of_study']);
showField("Total Experience", $pr['total_experience'].' Years');
showField("Relevant Experience", $pr['relevant_experience'].' Years');
showField("Current Job Title", $pr['current_job_title']);
showField("Occupation Mapping", $pr['occupation_mapping']);
showField("Target Country", $pr['target_country']);
?>

</div>
</div>

</div>


<div class="col-md-6">

<!-- ENGLISH DETAILS -->
<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">English Details</h6>
</div>
<div class="card-body">

<?php
showField("English Test Taken", $pr['english_test_taken']);
showField("Test Type", $pr['test_type']);
showField("Overall Score", $pr['overall_score']);
showField("Individual Scores", $pr['individual_scores']);
?>

</div>
</div>

<!-- FAMILY DETAILS -->
<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">Family Details</h6>
</div>
<div class="card-body">

<?php
showField("Marital Status", $pr['marital_status']);
showField("Spouse Included", $pr['spouse_included']);
showField("Spouse Qualification", $pr['spouse_qualification']);
showField("Spouse Experience", $pr['spouse_experience']);
showField("Spouse English", $pr['spouse_english']);
?>

</div>
</div>

<!-- ADDITIONAL INFORMATION -->
<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">Additional Information</h6>
</div>
<div class="card-body">

<?php
showField("Passport Available", $pr['passport_available']);
showField("Funds Available", $pr['funds_available']);
showField("Previous Visa Refusal", $pr['previous_visa_refusal']);
showField("Source", $pr['source']);
showField("Lead Status", $pr['lead_status']);
showField("Follow-up Date", $pr['followup_date']);
showField("Notes", $pr['notes']);
showField(
    "Total Fee",
    isset($pr['total_fee']) 
        ? '₹'.number_format($pr['total_fee'],2) 
        : '₹0.00'
);
?>

</div>
</div>

<!-- SYSTEM INFO -->
<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">System Information</h6>
</div>
<div class="card-body">

<?php
showField("Added By", $pr['employee_name']);
showField("Created Date", date("d M Y", strtotime($pr['created_at'])));
?>

</div>
</div>

</div>

<!-- ================= CASE TRACKING ================= -->
<div class="row">
<div class="col-12">

<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">PR Case Tracking</h6>
</div>

<div class="card-body">

<?php if ($case_id) { ?>

<div class="row g-4">

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Current Stage</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($case['current_stage'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Eligibility</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($case['eligibility_result'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Points</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($case['points'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Agreement</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($case['agreement_signed'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Program</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($process['program_stream'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Decision</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($process['decision_status'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Visa Grant</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($process['visa_grant_date'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Visa Expiry</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($process['visa_expiry_date'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Education Records</div>
<div class="fw-bold text-dark"><?= $eduCount ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Experience Records</div>
<div class="fw-bold text-dark"><?= $expCount ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Language Test</div>
<div class="fw-bold text-dark"><?= htmlspecialchars($language['test_type'] ?? '-') ?></div>
</div>
</div>

<div class="col-md-3">
<div class="border p-3 rounded bg-white">
<div class="text-muted small">Documents Uploaded</div>
<div class="fw-bold text-dark"><?= $docCount ?></div>
</div>
</div>

</div>

<?php } else { ?>

<div class="text-center py-4">

    <div class="text-muted mb-3">
        Case not initiated for this PR.
    </div>

    <?php if ($hasPayment) { ?>
        <a href="manage-case.php?lead_id=<?= $pr['lead_id'] ?>" 
           class="btn btn-primary btn-sm">
           Start PR Case
        </a>
    <?php } else { ?>
        <button class="btn btn-primary btn-sm" disabled>
           Complete Payment to Start Case
        </button>
    <?php } ?>

</div>

<?php } ?>

</div>
</div>

</div>
</div>

<?php
$eduList = null;

if ($case_id) {
    $eduStmt = $conn->prepare("
        SELECT *
        FROM pr_case_education
        WHERE case_id = ?
        ORDER BY year_completed DESC
    ");
    $eduStmt->bind_param("i", $case_id);
    $eduStmt->execute();
    $eduList = $eduStmt->get_result();
}
?>

<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">Education Details</h6>
</div>
<div class="card-body">

<?php if (!$case_id) { ?>

    <div class="text-muted">
        Initiate case to add education records.
    </div>

<?php } elseif ($eduList && $eduList->num_rows > 0) { ?>

    <table class="table table-bordered">
        <thead class="table-light">
        <tr>
            <th>Level</th>
            <th>Institution</th>
            <th>Field</th>
            <th>Year</th>
            <th>ECA</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $eduList->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['education_level']) ?></td>
                <td><?= htmlspecialchars($row['institution_name']) ?></td>
                <td><?= htmlspecialchars($row['field_of_study']) ?></td>
                <td><?= htmlspecialchars($row['year_completed']) ?></td>
                <td><?= htmlspecialchars($row['eca_status']) ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

<?php } else { ?>

    <div class="text-muted">
        No education records.
    </div>

<?php } ?>

</div>
</div>

<?php
$expList = null;

if ($case_id) {
    $expStmt = $conn->prepare("
        SELECT *
        FROM pr_case_experience
        WHERE case_id = ?
        ORDER BY duration_from DESC
    ");
    $expStmt->bind_param("i", $case_id);
    $expStmt->execute();
    $expList = $expStmt->get_result();
}
?>

<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">Experience Details</h6>
</div>
<div class="card-body">

<?php if (!$case_id) { ?>

    <div class="text-muted">
        Initiate case to add experience records.
    </div>

<?php } elseif ($expList && $expList->num_rows > 0) { ?>

    <table class="table table-bordered">
        <thead class="table-light">
        <tr>
            <th>Employer</th>
            <th>Job Title</th>
            <th>From</th>
            <th>To</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $expList->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['employer_name']) ?></td>
                <td><?= htmlspecialchars($row['job_title']) ?></td>
                <td><?= htmlspecialchars($row['duration_from']) ?></td>
                <td><?= htmlspecialchars($row['duration_to']) ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

<?php } else { ?>

    <div class="text-muted">
        No experience records.
    </div>

<?php } ?>

</div>
</div>

<?php
$langData = null;

if ($case_id) {
    $langStmt = $conn->prepare("
        SELECT *
        FROM pr_case_language
        WHERE case_id = ?
    ");
    $langStmt->bind_param("i", $case_id);
    $langStmt->execute();
    $langData = $langStmt->get_result()->fetch_assoc();
}
?>

<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">Language Details</h6>
</div>
<div class="card-body">

<?php if (!$case_id) { ?>

    <div class="text-muted">
        Initiate case to add language details.
    </div>

<?php } elseif ($langData) { ?>

    <?php
    showField("Test Type", $langData['test_type']);
    showField("TRF Number", $langData['trf_number']);
    showField("Listening", $langData['listening']);
    showField("Reading", $langData['reading']);
    showField("Writing", $langData['writing']);
    showField("Speaking", $langData['speaking']);
    showField("Overall Score", $langData['overall_score']);
    showField("Valid Till", $langData['valid_till']);
    ?>

<?php } else { ?>

    <div class="text-muted">
        No language details.
    </div>

<?php } ?>

</div>
</div>

<?php
$processData = null;

if ($case_id) {
    $procStmt = $conn->prepare("
        SELECT *
        FROM pr_case_process
        WHERE case_id = ?
    ");
    $procStmt->bind_param("i", $case_id);
    $procStmt->execute();
    $processData = $procStmt->get_result()->fetch_assoc();
}
?>

<div class="card mb-4">
<div class="card-header bg-light">
<h6 class="mb-0 text-muted">PR Process Details</h6>
</div>
<div class="card-body">

<?php if (!$case_id) { ?>

    <div class="text-muted">
        Initiate case to access process details.
    </div>

<?php } elseif ($processData) { ?>

    <?php
    showField("Program / Stream", $processData['program_stream']);
    showField("State Nomination / PNP", $processData['state_nomination']);
    showField("EOI / Profile ID", $processData['eoi_profile_id']);
    showField("Profile Submission Date", $processData['profile_submission_date']);
    showField("Invitation Received Date", $processData['invitation_received_date']);
    showField("Medical Status", $processData['medical_status']);
    showField("PCC India Status", $processData['pcc_india_status']);
    showField("PCC Other Country Status", $processData['pcc_other_status']);
    showField("Application Submitted Date", $processData['application_submitted_date']);
    showField("Application Reference Number", $processData['application_reference_number']);
    showField("Decision Status", $processData['decision_status']);
    showField("Visa Grant Date", $processData['visa_grant_date']);
    showField("Visa Expiry Date", $processData['visa_expiry_date']);
    ?>

<?php } else { ?>

    <div class="text-muted">
        Process details not updated yet.
    </div>

<?php } ?>

</div>
</div>

<!-- PAYMENT SECTION -->
<div class="row">
<div class="col-12">

<div class="card">
<div class="card-header bg-light d-flex justify-content-between align-items-center">
<h6 class="mb-0 text-muted">Payment Summary</h6>

<a href="pr-payment-add.php?lead_id=<?= $pr['lead_id'] ?>" 
class="btn btn-outline-primary btn-sm">
Add Payment
</a>

</div>

<div class="card-body">

<?php if ($hasPayment) { ?>

<div class="row mb-4">
<div class="col-md-4">
<div class="border p-3 rounded bg-light">
<div class="text-muted small">Total Fee</div>
<div class="fw-bold text-dark fs-5">
₹<?= number_format($total_fee,2) ?>
</div>
</div>
</div>

<div class="col-md-4">
<div class="border p-3 rounded bg-light">
<div class="text-muted small">Total Paid</div>
<div class="fw-bold text-dark fs-5">₹<?= number_format($total_paid,2) ?></div>
</div>
</div>

<div class="col-md-4">
<div class="border p-3 rounded bg-light">
<div class="text-muted small">Balance</div>
<div class="fw-bold">
<?php
$balance_class = $balance <= 0 ? 'text-success' : 'text-warning';
?>

<div class="fw-bold fs-5 <?= $balance_class ?>">
<?= $balance <= 0 
    ? 'Fully Paid' 
    : '₹'.number_format($balance,2).' Pending'; ?>
</div>
</div>
</div>
</div>
</div>

<!-- Installment History -->
<div class="table-responsive">
<table class="table table-bordered align-middle">
<thead class="table-light">
<tr>
<th>Date</th>
<th>Amount</th>
<th>Mode</th>
<th>Added By</th>
<th>Receipt</th>
</tr>
</thead>
<tbody>

<?php while ($pay = $paymentHistory->fetch_assoc()) { ?>
<tr>
<td><?= date("d M Y", strtotime($pay['payment_date'])) ?></td>
<td>₹<?= number_format($pay['amount'],2) ?></td>
<td><?= htmlspecialchars($pay['payment_mode']) ?></td>
<td><?= htmlspecialchars($pay['added_by'] ?? 'N/A') ?></td>
<td>
<?php if ($pay['receipt_file']) { ?>
<a href="pr-receipt-download.php?id=<?= $pay['id'] ?>" 
class="btn btn-light btn-sm border">
<i data-feather="download"></i>
</a>
<?php } else { ?>
<span class="text-muted small">N/A</span>
<?php } ?>
</td>
</tr>
<?php } ?>

</tbody>
</table>
</div>

<!-- CASE BUTTON -->
<div class="mt-3">
<?php if ($hasPayment) { ?>

    <a href="manage-case.php?lead_id=<?= $pr['lead_id'] ?>" 
       class="btn btn-outline-secondary btn-sm">
       Manage Case
    </a>

<?php } else { ?>

    <button class="btn btn-outline-secondary btn-sm" disabled>
        Manage Case (Complete Payment First)
    </button>

<?php } ?>
</div>

<?php } else { ?>

<div class="text-muted">
No payments recorded yet.
</div>

<?php } ?>

</div>
</div>

</div>
</div>

</div>

    

<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>



</body>
</html>