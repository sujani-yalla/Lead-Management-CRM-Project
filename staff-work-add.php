<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('staff_report')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$user_query = $conn->query("SELECT name FROM users WHERE id = $user_id");
$user = $user_query->fetch_assoc();
$staff_name = $user['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $work_date = $_POST['work_date'];
    // Restrict to last 2 days (for employees only)
if ($_SESSION['role'] !== 'admin') {

    $today = date('Y-m-d');
    $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));

    if ($work_date > $today || $work_date < $twoDaysAgo) {
        echo "<script>
            alert('You can only submit reports for today or the last 2 days.');
            window.location='staff-work-add.php';
        </script>";
        exit;
    }
}
    $total_calls = intval($_POST['total_calls']);
    $calls_connected = intval($_POST['calls_connected']);
    $busy_invalid_calls = intval($_POST['busy_invalid_calls']);
    $followups_done = intval($_POST['followups_done']);
    $working_hours = mysqli_real_escape_string($conn, $_POST['working_hours']);
    $daily_status = mysqli_real_escape_string($conn, $_POST['daily_status']);
    $other_work = mysqli_real_escape_string($conn, $_POST['other_work']);

    // Check if report already exists
    $check = $conn->query("
        SELECT id 
        FROM staff_work_reports 
        WHERE user_id = $user_id 
        AND work_date = '$work_date'
    ");

    if ($check->num_rows > 0) {
        echo "<script>
            alert('You have already submitted a report for this date. Please edit it instead.');
            window.location='staff-work-list.php';
        </script>";
        exit;
    }

    // Insert new record
    $insert = "
        INSERT INTO staff_work_reports (
            user_id, work_date, total_calls, calls_connected,
            busy_invalid_calls, followups_done,
            working_hours, daily_status, other_work
        ) VALUES (
            '$user_id', '$work_date', '$total_calls', '$calls_connected',
            '$busy_invalid_calls', '$followups_done',
            '$working_hours', '$daily_status', '$other_work'
        )
    ";

    $conn->query($insert);

    header("Location: staff-work-list.php");
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
    <title>Staff Working Data</title>
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
                                <h4>Leads</h4>
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
                                    <h4>Staff Working Form</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="row g-3 custom-input">
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label">Staff Name</label>
                                             <input type="text" class="form-control"
                                                    value="<?= htmlspecialchars($staff_name) ?>" readonly>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                        <label class="form-label">Work Date</label>
                                        <input type="date" name="work_date"
                                               value="<?= date('Y-m-d') ?>"
                                               class="form-control" required>
                                        </div>

                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip02">No. of Calls Made</label>
                                            <input class="form-control" type="number" name="total_calls" required>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltipUsername">Calls Connected</label>
                                            <div class="input-group has-validation">
                                              <input class="form-control" type="number" name="calls_connected" required>  
                                            </div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltipUsername">Busy / Invalid Calls</label>
                                            <div class="input-group has-validation">
                                                <input class="form-control" type="number" name="busy_invalid_calls" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltipUsername">Follow-ups Done</label>
                                            <div class="input-group has-validation">
                                                <input class="form-control" type="number" name="followups_done" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltipUsername">Working Hours</label>
                                            <div class="input-group has-validation">
                                               <input class="form-control" type="text" name="working_hours" placeholder="Eg: 9:30 AM – 6:30 PM">
                                            </div>
                                        </div>
                                        <div class="col-md-4 position-relative">
                                            <label class="form-label" for="validationTooltip04">Daily Work Status</label>
                                            <select class="form-select" name="daily_status" required>
                                                <option value="">Select</option>
                                                <option value="excellent">Excellent</option>
                                                <option value="good">Good</option>
                                                <option value="average">Average</option>
                                                <option value="need_improvement">Needs Improvement</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Other Work Done</label>
                                            <textarea name="other_work" class="form-control" rows="3"
                                               placeholder="Describe other tasks done today..."></textarea>
                                        </div>
                                        
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="submit" id="leadSubmit">Submit form</button>
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