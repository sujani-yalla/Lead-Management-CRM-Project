<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$visa_id = $_GET['visa_id'] ?? '';

if (empty($visa_id)) {
    die("Invalid Access");
}

if (isset($_POST['submit'])) {

    $country         = $_POST['country'];
    $job_role        = $_POST['job_role'];
    $contact_number  = $_POST['contact_number'];
    $status          = $_POST['application_status'];
    $documents       = $_POST['documents_required'];
    $jobs_applied    = $_POST['jobs_applied'];
    $time_period     = $_POST['time_period'];
    $documents_details = $_POST['documents_details'] ?? NULL;

    $stmt = $conn->prepare("
        INSERT INTO work_visa_marketing
(visa_id, country, job_role, contact_number, application_status, documents_required, documents_details, jobs_applied, time_period)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
$stmt->bind_param(
    "issssssis",
    $visa_id,
    $country,
    $job_role,
    $contact_number,
    $status,
    $documents,
    $documents_details,
    $jobs_applied,
    $time_period
);

    $stmt->execute();

    header("Location: work-visa-view.php?id=" . $visa_id);
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
    <title>Work Visa Marketing</title>
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
                                <!-- <h4>Leads</h4> -->
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
                                    <h4>Marketing Jobs – Work Visa</h4>
                                </div>
                                <div class="card-body">
                                    <!-- Success Modal -->
                                    <form class="row g-3 needs-validation custom-input" form method="POST" id="leadForm">
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip01">Country</label>
                                            <input class="form-control" name="country" type="text"
                                                placeholder="Country Name" required="">
                                            <div class="valid-tooltip">Looks good!</div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip02">Job Type</label>
                                            <input class="form-control" name="job_role" type="text"
                                                placeholder="Job Role" required="">
                                            <div class="valid-tooltip">Looks good!</div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltipUsername">Contact Number</label>
                                            <div class="input-group has-validation">
                                                <input class="form-control" name="contact_number" type="text"
                                                placeholder="Mobile Number" required="">
                                            </div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip04">Application Status</label>
                                            <input class="form-control" name="application_status" type="text"
placeholder="Enter Application Status" required="">
    
                                            
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip04">Documents Required</label>
                                            <select class="form-select" name="documents_required" id="documents_required" required>
                                              <option value="">Select</option>
                                              <option value="Yes">Yes</option>
                                              <option value="No">No</option>
                                             </select>
                                            <div class="invalid-tooltip">Please select a valid state.</div>
                                        </div>
                                        <div class="col-md-4 position-relative" id="documents_text_div" style="display:none;">
    <label class="form-label">Mention Required Documents</label>
    <input class="form-control" name="documents_details" type="text"
        placeholder="Enter document details">
</div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltipUsername">No. of Jobs Applied</label>
                                            <div class="input-group has-validation">
                                                <input class="form-control" name="jobs_applied" type="number"
                                                placeholder="Count" required="">
                                            </div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltipUsername">Time Period</label>
                                            <div class="input-group has-validation">
                                                <input class="form-control" name="time_period" type="text"
                                                 placeholder="e.g. 3 Months" required="">
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit" name="submit" id="leadSubmit">Submit form</button>
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
   <script>
document.getElementById("documents_required").addEventListener("change", function () {
    var docDiv = document.getElementById("documents_text_div");

    if (this.value === "Yes") {
        docDiv.style.display = "block";
    } else {
        docDiv.style.display = "none";
    }
});
</script>


</body>

</html>