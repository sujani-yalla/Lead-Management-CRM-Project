<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('work_visa')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['lead_id'])) {
    die("Invalid Access");
}

$leadId = intval($_GET['lead_id']);
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

/* ===== FETCH LEAD INFO ===== */
if ($role === 'admin') {

    $fetchLead = $conn->prepare("
        SELECT id, lead_name, mobile, email, assigned_to
        FROM leads
        WHERE id = ?
    ");
    $fetchLead->bind_param("i", $leadId);

} else {

    $fetchLead = $conn->prepare("
        SELECT id, lead_name, mobile, email, assigned_to
        FROM leads
        WHERE id = ?
        AND assigned_to = ?
    ");
    $fetchLead->bind_param("ii", $leadId, $userId);
}

$fetchLead->execute();
$leadResult = $fetchLead->get_result();
$leadData = $leadResult->fetch_assoc();

if (!$leadData) {
    die("Unauthorized or Lead not found");
}

$fetchLead->close();


/* ===== INSERT WORK VISA CASE ===== */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $address = $_POST['address'];
    $processStart = $_POST['process_start_date'];
    $country = $_POST['applying_country'];
    $applyDate = $_POST['applying_date'];
    $jobCategory = $_POST['job_category'];
    $experience = $_POST['experience_years'];
    $qualification = $_POST['qualification'];
    $englishTest = $_POST['english_test'];
    $passport = $_POST['passport_number'];
    $refusal = $_POST['previous_visa_refusal'];
    $pendingDocs = $_POST['pending_documents'];
    $salary = $_POST['salary'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("
        INSERT INTO work_visas
        (lead_id, address, process_start_date, applying_country,
         applying_date, job_category, experience_years,
         qualification, english_test, passport_number,
         previous_visa_refusal, pending_documents,
         salary, status, notes, created_by, assigned_to)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isssssissssssssii",
        $leadId,
        $address,
        $processStart,
        $country,
        $applyDate,
        $jobCategory,
        $experience,
        $qualification,
        $englishTest,
        $passport,
        $refusal,
        $pendingDocs,
        $salary,
        $status,
        $notes,
        $userId,
        $leadData['assigned_to']
    );

    $stmt->execute();
    $stmt->close();

    header("Location: lead-list.php?work_added=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Riho admin is super flexible, powerful, clean &amp; modern responsive bootstrap 5 admin template with unlimited possibilities.">
    <meta name="keywords"
        content="admin template, Riho admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title>Work Visas Adding</title>
    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../../css2-1?family=Montserrat:wght@200;300;400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <!-- ico-font-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/icofont.css">
    <!-- Themify icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/themify.css">
    <!-- Flag icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/flag-icon.css">
    <!-- Feather icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/feather-icon.css">
    <!-- Plugins css start-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick-theme.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/scrollbar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/animate.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/echart.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/date-picker.css">
    <!-- Plugins css Ends-->
    <!-- Bootstrap css-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <!-- App css-->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link id="color" rel="stylesheet" href="assets/css/color-1.css" media="screen">
    <!-- Responsive css-->
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
</head>

<body>
    <!-- loader starts-->
    <div class="loader-wrapper">
        <div class="loader">
            <div class="loader4"></div>
        </div>
    </div>
    <!-- loader ends-->
    <!-- tap on top starts-->
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <!-- tap on tap ends-->
    <!-- page-wrapper Start-->
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        <?php include "header.php";?>
        <!-- Page Header Ends                              -->
        <!-- Page Body Start-->
        <div class="page-body-wrapper">
            <!-- Page Sidebar Start-->
           <?php include "sidebar.php";?>
            <!-- Page Sidebar Ends-->
            <div class="page-body">
                <div class="container-fluid">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-6">
                                <h4>Work visa Details</h4>
                            </div>
                            <div class="col-6">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">
                                            <svg class="stroke-icon">
                                                <use href="assets/svg/icon-sprite.svg#stroke-home"></use>
                                            </svg></a></li>
                                    <li class="breadcrumb-item">Dashboard</li>
                                    <li class="breadcrumb-item active">Default</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Container-fluid starts-->
                <div class="container-fluid">
                    <div class="row size-column">
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Work visa Details Form</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Success Modal -->
                                    <div class="modal fade" id="successModal" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Success</h5>
                                        </div>
                                        <div class="modal-body text-center">
                                            <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
                                            <p class="fs-5">work visa details added successfully</p>
                                        </div>
                                        </div>
                                    </div>
                                    </div>

                                       <form method="POST" class="row g-4">

<!-- Applicant Info (Read Only) -->
<div class="col-12">
    <h5 class="fw-bold text-primary">Applicant Information</h5>
</div>

<div class="col-md-4">
    <label class="form-label">Full Name</label>
    <input type="text" class="form-control"
           value="<?= htmlspecialchars($leadData['lead_name']) ?>" disabled>
</div>

<div class="col-md-4">
    <label class="form-label">Mobile</label>
    <input type="text" class="form-control"
           value="<?= htmlspecialchars($leadData['mobile']) ?>" disabled>
</div>

<div class="col-md-4">
    <label class="form-label">Email</label>
    <input type="text" class="form-control"
           value="<?= htmlspecialchars($leadData['email']) ?>" disabled>
</div>

<!-- Work Visa Details -->
<div class="col-12 mt-4">
    <h5 class="fw-bold text-primary">Work Visa Details</h5>
</div>

<div class="col-md-6">
    <label class="form-label">Address</label>
    <textarea name="address" class="form-control" required></textarea>
</div>

<div class="col-md-3">
    <label class="form-label">Process Start Date</label>
    <input type="date" name="process_start_date" class="form-control" required>
</div>

<div class="col-md-3">
    <label class="form-label">Applying Date</label>
    <input type="date" name="applying_date" class="form-control">
</div>

<div class="col-md-4">
    <label class="form-label">Applying Country</label>
    <input type="text" name="applying_country" class="form-control" required>
</div>

<div class="col-md-4">
    <label class="form-label">Job Category / Sector</label>
    <input type="text" name="job_category" class="form-control">
</div>

<div class="col-md-2">
    <label class="form-label">Experience (Years)</label>
    <input type="number" name="experience_years" class="form-control">
</div>

<div class="col-md-2">
    <label class="form-label">Qualification</label>
    <input type="text" name="qualification" class="form-control">
</div>

<div class="col-md-3">
    <label class="form-label">English Test</label>
    <select name="english_test" class="form-select">
        <option value="None">None</option>
        <option value="IELTS">IELTS</option>
        <option value="PTE">PTE</option>
    </select>
</div>

<div class="col-md-3">
    <label class="form-label">Passport Number</label>
    <input type="text" name="passport_number" class="form-control">
</div>

<div class="col-md-3">
    <label class="form-label">Previous Visa Refusal</label>
    <select name="previous_visa_refusal" class="form-select">
        <option value="No">No</option>
        <option value="Yes">Yes</option>
    </select>
</div>

<div class="col-md-3">
    <label class="form-label">Salary</label>
    <input type="text" name="salary" class="form-control">
</div>

<div class="col-12">
    <label class="form-label">Pending Documents</label>
    <textarea name="pending_documents" class="form-control"></textarea>
</div>

<div class="col-md-6">
    <label class="form-label">Application Status</label>
    <select name="status" class="form-select" required>
        <option>Screening Completed</option>
        <option>Profile Created</option>
        <option>CV Prepared</option>
        <option>Sent to Employer</option>
        <option>Interview Scheduled</option>
        <option>Interview Cleared</option>
        <option>Offer Letter Received</option>
        <option>Offer Accepted</option>
        <option>COS Received</option>
        <option>Visa Process Started</option>
        <option>Docs Pending</option>
        <option>Visa Filed</option>
        <option>Decision Awaited</option>
        <option>Approved</option>
        <option>Travel Completed</option>
        <option>Closed</option>
    </select>
</div>

<div class="col-12">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control"></textarea>
</div>

<div class="col-12 text-end mt-4">
    <button type="submit" class="btn btn-primary px-4">
        Create Work Visa
    </button>
</div>

</form>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Container-fluid Ends-->
            </div>
            <!-- footer start-->
            <?php include "footer.php";?>
        </div>
    </div>
    <!-- latest jquery-->
    <script src="assets/js/jquery.min.js"></script>
    <!-- Bootstrap js-->
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- feather icon js-->
    <script src="assets/js/icons/feather-icon/feather.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather-icon.js"></script>
    <!-- scrollbar js-->
    <script src="assets/js/scrollbar/simplebar.js"></script>
    <script src="assets/js/scrollbar/custom.js"></script>
    <!-- Sidebar jquery-->
    <script src="assets/js/config.js"></script>
    <!-- Plugins JS start-->
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/sidebar-pin.js"></script>
    <script src="assets/js/slick/slick.min.js"></script>
    <script src="assets/js/slick/slick.js"></script>
    <script src="assets/js/header-slick.js"></script>
    <script src="assets/js/chart/apex-chart/apex-chart.js"></script>
    <script src="assets/js/chart/apex-chart/stock-prices.js"></script>
    <script src="assets/js/chart/apex-chart/moment.min.js"></script>
    <script src="assets/js/chart/echart/esl.js"></script>
    <script src="assets/js/chart/echart/config.js"></script>
    <script src="assets/js/chart/echart/pie-chart/facePrint.js"></script>
    <script src="assets/js/chart/echart/pie-chart/testHelper.js"></script>
    <script src="assets/js/chart/echart/pie-chart/custom-transition-texture.js"></script>
    <script src="assets/js/chart/echart/data/symbols.js"></script>
    <!-- calendar js-->
    <script src="assets/js/datepicker/date-picker/datepicker.js"></script>
    <script src="assets/js/datepicker/date-picker/datepicker.en.js"></script>
    <script src="assets/js/datepicker/date-picker/datepicker.custom.js"></script>
    <script src="assets/js/dashboard/dashboard_3.js"></script>
    <!-- Plugins JS Ends-->
    <!-- Theme js-->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/theme-customizer/customizer.js"></script>
<!-- 
<script>
document.getElementById("leadSubmit").addEventListener("click", function () {

    // OPTIONAL: basic validation
    let requiredFilled = true;
    document.querySelectorAll("[required]").forEach(function (el) {
        if (!el.value) {
            requiredFilled = false;
        }
    });

    if (!requiredFilled) {
        alert("Please fill all required fields");
        return;
    }

    // Show success modal
    let modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();

    // Close modal after 2 seconds
    setTimeout(function () {
        modal.hide();
    }, 2000);

    // Reset form after 2.5 seconds
    setTimeout(function () {
        document.getElementById("leadForm").reset();
    }, 2500);
});
</script>
-->

</body>

</html>