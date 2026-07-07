<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('visitor_visa')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['lead_id'])) {
    echo "Invalid Access";
    exit;
}

$employeeId = $_SESSION['user_id'];
$leadId = intval($_GET['lead_id']);

// Fetch Lead Details
$leadQuery = $conn->prepare("
    SELECT leads.*, users.name AS employee_name
    FROM leads
    LEFT JOIN users ON leads.assigned_to = users.id
    WHERE leads.id = ?
");
$leadQuery->bind_param("i", $leadId);
$leadQuery->execute();
$leadResult = $leadQuery->get_result();
$leadData = $leadResult->fetch_assoc();

if (!$leadData) {
    echo "Lead not found";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $country = $_POST['country'];
    $applicationStatus = $_POST['application_status'];
    $visaStatus = $_POST['visa_status'];
    $processingStartDate = $_POST['processing_start_date'];
    $travelDate = $_POST['travel_date'];
    $travelDuration = $_POST['travel_duration'];
    $purposeOfVisit = $_POST['purpose_of_visit'];
    $passportNumber = $_POST['passport_number'];
    $previousVisaRefusal = $_POST['previous_visa_refusal'];
    $previousVisitCountries = $_POST['previous_visit_countries'];
    $pendingDocuments = $_POST['pending_documents'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("
        INSERT INTO visas
        (lead_id, visa_type, country, processing_start_date, purpose_of_visit,
         travel_date, travel_duration, passport_number,
         previous_visa_refusal, previous_visit_countries,
         status, visa_status, pending_documents, notes, created_by)
        VALUES (?, 'visitor', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "issssisssssssi",
        $leadId,
        $country,
        $processingStartDate,
        $purposeOfVisit,
        $travelDate,
        $travelDuration,
        $passportNumber,
        $previousVisaRefusal,
        $previousVisitCountries,
        $applicationStatus,
        $visaStatus,
        $pendingDocuments,
        $notes,
        $employeeId
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: visitor-visa-list.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
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
    <title>Visitors Adding</title>
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
                                <h4>Visitors Details</h4>
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
                                    <h4>Visitor Details Form</h4>
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
                                            <p class="fs-5">Visitor details added successfully</p>
                                        </div>
                                        </div>
                                    </div>
                                    </div>

                                   <form method="POST" class="row g-3">

    <!-- Lead Summary -->
    <div class="col-12">
        <div class="alert alert-light border">
            <strong>Lead Information</strong><br>
            Name: <?= htmlspecialchars($leadData['lead_name']) ?><br>
            Mobile: <?= htmlspecialchars($leadData['mobile']) ?><br>
            Email: <?= htmlspecialchars($leadData['email']) ?><br>
            Assigned To: <?= htmlspecialchars($leadData['employee_name']) ?>
        </div>
    </div>

    <!-- Applying Country -->
    <div class="col-md-4">
        <label class="form-label">Applying Country</label>
        <select name="country" class="form-select" required>
            <option value="">Select</option>
            <option value="UK">UK</option>
            <option value="Schengen">Schengen</option>
            <option value="USA">USA</option>
            <option value="Others">Others</option>
        </select>
    </div>

    <!-- Purpose of Visit -->
    <div class="col-md-4">
        <label class="form-label">Purpose of Visit</label>
        <select name="purpose_of_visit" class="form-select" required>
            <option value="">Select</option>
            <option value="Tourism">Tourism</option>
            <option value="Family Visit">Family Visit</option>
            <option value="Business">Business</option>
            <option value="Invitation">Invitation</option>
        </select>
    </div>

    <!-- Processing Start Date -->
    <div class="col-md-4">
        <label class="form-label">Processing Start Date</label>
        <input type="date" name="processing_start_date" class="form-control">
    </div>

    <!-- Travel Date -->
    <div class="col-md-4">
        <label class="form-label">Travel Date</label>
        <input type="date" name="travel_date" class="form-control" required>
    </div>

    <!-- Travel Duration -->
    <div class="col-md-4">
        <label class="form-label">Travel Duration (Days)</label>
        <input type="number" name="travel_duration" class="form-control">
    </div>

    <!-- Passport Number -->
    <div class="col-md-4">
        <label class="form-label">Passport Number</label>
        <input type="text" name="passport_number" class="form-control">
    </div>

    <!-- Previous Visa Refusal -->
    <div class="col-md-4">
        <label class="form-label">Previous Visa Refusal</label>
        <select name="previous_visa_refusal" class="form-select">
            <option value="No">No</option>
            <option value="Yes">Yes</option>
        </select>
    </div>

    <!-- Previous Visit Countries -->
    <div class="col-md-8">
        <label class="form-label">Previous Visit Countries</label>
        <textarea name="previous_visit_countries" class="form-control"></textarea>
    </div>

    <!-- Application Status -->
    <div class="col-md-4">
        <label class="form-label">Application Status</label>
        <select name="application_status" class="form-select" required>
            <option value="pending">Pending</option>
            <option value="submitted">Submitted</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <!-- Visa Status -->
    <div class="col-md-4">
        <label class="form-label">Visa Status</label>
        <select name="visa_status" class="form-select" required>
            <option value="processing">Processing</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="hold">On Hold</option>
        </select>
    </div>

    <!-- Pending Documents -->
    <div class="col-md-4">
        <label class="form-label">Pending Documents</label>
        <textarea name="pending_documents" class="form-control"></textarea>
    </div>

    <!-- Notes -->
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control"></textarea>
    </div>

    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save Visitor Details</button>
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