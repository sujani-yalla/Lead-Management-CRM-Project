<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('calling_followup')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['lead_id']) || !is_numeric($_GET['lead_id'])) {
    die("Invalid Lead");
}

$lead_id = intval($_GET['lead_id']);
$user_id = $_SESSION['user_id'];

/* Verify lead ownership for employee */
if ($_SESSION['role'] !== 'admin') {
    $check = $conn->prepare("SELECT id FROM leads WHERE id = ? AND assigned_to = ?");
    $check->bind_param("ii", $lead_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        die("Unauthorized Access");
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $call_type = $_POST['call_type'];
    $call_datetime = $_POST['call_datetime'];
    $call_status = $_POST['call_status'];
    $call_outcome = $_POST['call_outcome'];
    $next_followup_date = !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : NULL;
    $remarks = $_POST['remarks'];

    $stmt = $conn->prepare("
        INSERT INTO call_logs 
        (lead_id, user_id, call_type, call_datetime, call_status, call_outcome, next_followup_date, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iissssss",
        $lead_id,
        $user_id,
        $call_type,
        $call_datetime,
        $call_status,
        $call_outcome,
        $next_followup_date,
        $remarks
    );

    if ($stmt->execute()) {
        header("Location: call-history.php?lead_id=" . $lead_id);
        exit;
    } else {
        $error = "Something went wrong.";
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
    <title>Calling & Follow-up Record</title>
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
   <!-- <div class="loader-wrapper">
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
        <?php include "header.php"; ?>
        <!-- Page Header Ends                              -->
        <!-- Page Body Start-->
        <div class="page-body-wrapper">
            <!-- Page Sidebar Start-->
            <?php include "sidebar.php"; ?>

            <!-- Page Sidebar Ends-->
            <div class="page-body">
                <div class="container-fluid">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-6">
                                <!-- <h4>Default</h4> -->
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
                        <div class="col-xl-7 offset-xl-2">
                            <div class="card height-equal">
                                <div class="card-header text-center">
                                    <h4>Calling & Follow-up Record</h4>
                                </div>
                                <div class="card-body custom-input">
                                    <form method=POST class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label" for="first-name">Call Type</label>
                                             <select name="call_type" class="form-select"  required="">
                                                <option value="">Select</option>
                                                <option value="website"> Website</option>
                                                <option value="instagram">Instagram</option>
                                                <option value="facebook">Facebook</option>
                                                <option value="linkedin">LinkedIn</option>
                                                <option value="ivr">IVR</option>
                                                <option value="normal_call">Normal Call</option>
                                                <option value="telecalling">Telecalling</option>
                                                <option>Other Platforms</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="exampleFormControlInput1">Call Date & Time</label>
                                            <input type="datetime-local" 
                                                   name="call_datetime" 
                                                   class="form-control" 
                                                   value="<?= date('Y-m-d\TH:i') ?>" 
                                                   required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="validationDefault04">Call Status</label>
                                            <select  name="call_status" class="form-select"  required="">
                                                <option value="">Select</option>
                                                <option value="answered">Answered</option>
                                                <option value="busy">Busy</option>
                                                <option value="not_reachable">Not Reachable</option>
                                                <option value="switched_off">Switched Off</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="validationDefault04">Call Outcome</label>
                                            <select name="call_outcome" class="form-select"  required="">
                                                  <option value="">Select</option>
                                                    <option value="interested">Interested</option>
                                                    <option value="not_interested">Not Interested</option>
                                                    <option value="followup_required">Follow-up Required</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="formFile">Next Follow-up Date</label>
                                            <input type="date" name="next_followup_date" class="form-control">
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label" for="exampleFormControlTextarea1">Remarks</label>
                                            <textarea class="form-control" name="remarks" rows="3"></textarea>
                                        </div>
                                      
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit" name="submit">Submit</button>
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
            <?php include "footer.php"; ?>
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