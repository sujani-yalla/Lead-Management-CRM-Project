<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('ca_legal')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if (isset($_POST['submit'])) {

    $client_name       = mysqli_real_escape_string($conn, $_POST['client_name']);
    $service_type      = mysqli_real_escape_string($conn, $_POST['service_type']);
    $documents_status  = mysqli_real_escape_string($conn, $_POST['documents_status']);
    $completion_status = mysqli_real_escape_string($conn, $_POST['completion_status']);
    $remarks           = mysqli_real_escape_string($conn, $_POST['remarks']);

    $insert = mysqli_query($conn, "
        INSERT INTO legal_cases 
        (client_name, service_type, documents_status, completion_status, remarks, created_by) 
        VALUES 
        ('$client_name', '$service_type', '$documents_status', '$completion_status', '$remarks', '$userId')
    ");

    if ($insert) {
        header("Location: legal-list.php?success=1");
        exit();
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
    <title>Cases Adding</title>
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
    <!--<div class="loader-wrapper">
        <div class="loader">
            <div class="loader4"></div>
        </div>
    </div>-->
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
                                <h4>Cases</h4>
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
                                    <h4>Case Details Form</h4>
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
                                            <p class="fs-5">Case added successfully</p>
                                        </div>
                                        </div>
                                    </div>
                                    </div>

                                    <!-- Form -->
                                    <form method="POST" class="row g-3 needs-validation custom-input">

                                        <!-- Client Name -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Client Name</label>
                                            <input type="text" class="form-control" name="client_name" placeholder="Enter Client Name" required>
                                        </div>

                                        <!-- Service Type -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Service Type</label>
                                            <select class="form-select" name="service_type" required>
                                                <option value="" selected disabled>Select Service</option>
                                                <option value="ca">CA</option>
                                                <option value="lawyer">Lawyer</option>
                                            </select>
                                        </div>

                                        <!-- Documents Status -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Documents Status</label>
                                            <select class="form-select" name="documents_status" required>
                                                <option value="" selected disabled>Select Status</option>
                                                <option value="pending">Pending</option>
                                                <option value="submitted">Submitted</option>
                                                <option value="verified">Verified</option>
                                            </select>
                                        </div>

                                        <!-- Completion Status -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Completion Status</label>
                                            <select class="form-select" name="completion_status" required>
                                                <option value="" selected disabled>Select Status</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="completed">Completed</option>
                                                <option value="on_hold">On Hold</option>
                                            </select>
                                        </div>

                                        <!-- Remarks -->
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Remarks</label>
                                            <input type="text" class="form-control" name="remarks" placeholder="Enter Remarks" required>
                                        </div>

                                        <!-- Submit -->
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit" name="submit" id="legalSubmit">
                                                Submit form
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


</body>

</html>