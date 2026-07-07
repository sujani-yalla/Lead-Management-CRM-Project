<?php
session_start();
require 'db.php';
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('student_visa')) {
    http_response_code(403);
    die("Access Denied");
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$visa_id = $_GET['id'] ?? '';

if (empty($visa_id)) {
    header("Location: student-visa-list.php");
    exit;
}

/* Fetch Complete Student Visa Record */
if ($_SESSION['role'] === 'admin') {

    $sql = "
    SELECT 
        v.*,
        l.lead_name,
        l.mobile,
        l.email,
        l.lead_source,
        u.name AS employee_name,
        svd.*
    FROM visas v
    JOIN leads l ON v.lead_id = l.id
    LEFT JOIN users u ON v.assigned_to = u.id
    LEFT JOIN student_visa_details svd ON svd.visa_id = v.id
    WHERE v.id = ? AND v.visa_type = 'student'
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $visa_id);

} else {

    $sql = "
    SELECT 
        v.*,
        l.lead_name,
        l.mobile,
        l.email,
        l.lead_source,
        u.name AS employee_name,
        svd.*
    FROM visas v
    JOIN leads l ON v.lead_id = l.id
    LEFT JOIN users u ON v.assigned_to = u.id
    LEFT JOIN student_visa_details svd ON svd.visa_id = v.id
    WHERE v.id = ?
    AND v.visa_type = 'student'
    AND l.assigned_to = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $visa_id, $_SESSION['user_id']);
}
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "Invalid Student Visa Record";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Visa Case Details</title>
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>

<body>
<div class="container-fluid">
<div class="row">

<div class="col-12 mb-3 d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Student Visa Case Details</h4>
    <a href="student-visa-list.php" class="btn btn-outline-primary btn-sm">
        ← Back to List
    </a>
</div>

<!-- Lead Information -->
<div class="col-md-6">
<div class="card shadow-sm">
<div class="card-header"><h6 class="mb-0">Lead Information</h6></div>
<div class="card-body">
<p><strong>Name:</strong> <?= htmlspecialchars($data['lead_name']) ?></p>
<p><strong>Mobile:</strong> <?= htmlspecialchars($data['mobile']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($data['email']) ?></p>
<p><strong>Lead Source:</strong> <?= htmlspecialchars($data['lead_source'] ?? '-') ?></p>
<p><strong>Assigned Employee:</strong> <?= htmlspecialchars($data['employee_name'] ?? '-') ?></p>
</div>
</div>
</div>

<!-- Visa Details -->
<div class="col-md-6">
<div class="card shadow-sm">
<div class="card-header"><h6 class="mb-0">Visa Details</h6></div>
<div class="card-body">
<p><strong>Country:</strong> <?= htmlspecialchars($data['country']) ?></p>
<p><strong>Processing Start:</strong> <?= htmlspecialchars($data['processing_start_date'] ?? '-') ?></p>
<p><strong>Passport Number:</strong> <?= htmlspecialchars($data['passport_number'] ?? '-') ?></p>
<p><strong>Application Status:</strong> <?= ucfirst($data['status']?? '-') ?></p>
<p><strong>Visa Status:</strong> <?= ucfirst($data['visa_status']) ?></p>
<p><strong>Pending Documents:</strong> <?= htmlspecialchars($data['pending_documents'] ?? '-') ?></p>
</div>
</div>
</div>

<!-- Academic Details -->
<div class="col-md-6 mt-3">
<div class="card shadow-sm">
<div class="card-header"><h6 class="mb-0">Academic Details</h6></div>
<div class="card-body">
<p><strong>Course:</strong> <?= htmlspecialchars($data['course'] ?? '-') ?></p>
<p><strong>University:</strong> <?= htmlspecialchars($data['university'] ?? '-') ?></p>
<p><strong>Tuition Fees:</strong> <?= htmlspecialchars($data['tuition_fees'] ?? '-') ?></p>
<p><strong>Course Duration:</strong> <?= htmlspecialchars($data['course_duration'] ?? '-') ?></p>
<p><strong>Intake:</strong> <?= htmlspecialchars($data['intake'] ?? '-') ?></p>
<p><strong>University Deadline:</strong> <?= htmlspecialchars($data['university_deadline'] ?? '-') ?></p>
</div>
</div>
</div>

<!-- Offer & Financial -->
 <div class="col-md-6 mt-3">
<div class="card shadow-sm">
<div class="card-header"><h6 class="mb-0">Offer & Financial Details</h6></div>
<div class="card-body">

<p><strong>Offer Letter Status:</strong> 
<?= ucfirst($data['offer_letter_status'] ?? '-') ?></p>

<p><strong>Loan Required:</strong> 
<?= htmlspecialchars($data['loan_required'] ?? '-') ?></p>

<p><strong>Student Address:</strong> 
<?= htmlspecialchars($data['student_address'] ?? '-') ?></p>

</div>
</div>
</div>

<!-- Notes -->
<div class="col-12 mt-3">
<div class="card shadow-sm">
<div class="card-header"><h6 class="mb-0">Notes / Comments</h6></div>
<div class="card-body">
<p><?= nl2br(htmlspecialchars($data['notes'] ?? 'No notes added')) ?></p>
</div>
</div>
</div>

</div>
</div>


<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>

</body>
</html>