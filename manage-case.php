<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['lead_id'])) {
    die("Lead ID missing");
}

$lead_id = intval($_GET['lead_id']);

/* ===== ACTIVE TAB CONTROL ===== */
$allowed_tabs = ['overview','personal','education','experience','language','process','documents'];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs)
    ? $_GET['tab']
    : 'overview';

/* ===== Check Lead Exists ===== */
$leadStmt = $conn->prepare("SELECT lead_name FROM leads WHERE id = ?");
$leadStmt->bind_param("i", $lead_id);
$leadStmt->execute();
$lead = $leadStmt->get_result()->fetch_assoc();

if (!$lead) {
    die("Lead not found");
}

/* ===== Get PR Enquiry ID (for Back Button) ===== */
$prStmt = $conn->prepare("SELECT id FROM pr_enquiries WHERE lead_id = ?");
$prStmt->bind_param("i", $lead_id);
$prStmt->execute();
$prData = $prStmt->get_result()->fetch_assoc();

$pr_id = $prData['id'] ?? 0;

/* ===== Get or Create Case ===== */
$caseStmt = $conn->prepare("SELECT * FROM pr_case_details WHERE lead_id = ?");
$caseStmt->bind_param("i", $lead_id);
$caseStmt->execute();
$case = $caseStmt->get_result()->fetch_assoc();

if (!$case) {
    $create = $conn->prepare("
        INSERT INTO pr_case_details (lead_id, current_stage, created_by, created_at)
        VALUES (?, 'Eligibility Check', ?, NOW())
    ");
    $create->bind_param("ii", $lead_id, $_SESSION['user_id']);
    $create->execute();
    $case_id = $create->insert_id;
} else {
    $case_id = $case['id'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage PR Case</title>
    <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid py-4">

<div class="d-flex justify-content-between align-items-center mb-4">

    <h4 class="mb-0">
        Manage PR Case – <?= htmlspecialchars($lead['lead_name']) ?>
    </h4>

    <a href="pr-view.php?id=<?= $pr_id ?>"
       class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to View
    </a>

</div>

<ul class="nav nav-tabs">

    <li class="nav-item">
        <a class="nav-link <?= $active_tab=='overview'?'active':'' ?>"
           href="manage-case.php?lead_id=<?= $lead_id ?>&tab=overview">Overview</a>
    </li>

    <li class="nav-item">
        <a class="nav-link <?= $active_tab=='personal'?'active':'' ?>"
           href="manage-case.php?lead_id=<?= $lead_id ?>&tab=personal">Personal</a>
    </li>

    <li class="nav-item">
        <a class="nav-link <?= $active_tab=='education'?'active':'' ?>"
           href="manage-case.php?lead_id=<?= $lead_id ?>&tab=education">Education</a>
    </li>

    <li class="nav-item">
        <a class="nav-link <?= $active_tab=='experience'?'active':'' ?>"
           href="manage-case.php?lead_id=<?= $lead_id ?>&tab=experience">Experience</a>
    </li>

    <li class="nav-item">
        <a class="nav-link <?= $active_tab=='language'?'active':'' ?>"
           href="manage-case.php?lead_id=<?= $lead_id ?>&tab=language">Language</a>
    </li>

    <li class="nav-item">
        <a class="nav-link <?= $active_tab=='process'?'active':'' ?>"
           href="manage-case.php?lead_id=<?= $lead_id ?>&tab=process">Process</a>
    </li>

    <li class="nav-item">
        <a class="nav-link <?= $active_tab=='documents'?'active':'' ?>"
           href="manage-case.php?lead_id=<?= $lead_id ?>&tab=documents">Documents</a>
    </li>

</ul>

<div class="mt-4">

<?php
switch($active_tab) {

    case 'personal':
        include "includes/pr/case-personal.php";
        break;

    case 'education':
        include "includes/pr/case-education.php";
        break;

    case 'experience':
        include "includes/pr/case-experience.php";
        break;

    case 'language':
        include "includes/pr/case-language.php";
        break;

    case 'process':
        include "includes/pr/case-process.php";
        break;

    case 'documents':
        include "includes/pr/case-documents.php";
        break;

    default:
        include "includes/pr/case-overview.php";
}
?>

</div>

</div>

<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>