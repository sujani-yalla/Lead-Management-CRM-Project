<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('staff_report')) {
    http_response_code(403);
    die("Access Denied");
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];

if (!isset($_GET['id'])) {
    die("Invalid ID");
}

$id = intval($_GET['id']);

// Fetch record
$sql = "
SELECT sw.*, u.name
FROM staff_work_reports sw
JOIN users u ON u.id = sw.user_id
WHERE sw.id = $id
";

$result = $conn->query($sql);
$data = $result->fetch_assoc();

if (!$data) {
    die("Record not found");
}

// Permission check
if ($role !== 'admin' && $data['user_id'] != $userId) {
    die("Access denied");
}

// Same-day edit restriction for employees
if ($role !== 'admin') {

    $today = date('Y-m-d');
$twoDaysAgo = date('Y-m-d', strtotime('-2 days'));

if ($data['work_date'] > $today || $data['work_date'] < $twoDaysAgo) {
    echo "<script>
        alert('You can only edit reports within the last 2 days.');
        window.location='staff-work-list.php';
    </script>";
    exit;
}
}

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $work_date = $_POST['work_date'];
    $total_calls = intval($_POST['total_calls']);
    $calls_connected = intval($_POST['calls_connected']);
    $busy_invalid_calls = intval($_POST['busy_invalid_calls']);
    $followups_done = intval($_POST['followups_done']);
    $working_hours = mysqli_real_escape_string($conn, $_POST['working_hours']);
    $daily_status = mysqli_real_escape_string($conn, $_POST['daily_status']);
    $other_work = mysqli_real_escape_string($conn, $_POST['other_work']);

    // Check if another record already exists for same user & date
$check = $conn->query("
    SELECT id 
    FROM staff_work_reports
    WHERE user_id = {$data['user_id']}
    AND work_date = '$work_date'
    AND id != $id
");

if ($check->num_rows > 0) {
    echo "<script>
        alert('A report already exists for this date. Please choose another date.');
        window.history.back();
    </script>";
    exit;
}

    $update = "
    UPDATE staff_work_reports SET
        work_date = '$work_date',
        total_calls = '$total_calls',
        calls_connected = '$calls_connected',
        busy_invalid_calls = '$busy_invalid_calls',
        followups_done = '$followups_done',
        working_hours = '$working_hours',
        daily_status = '$daily_status',
        other_work = '$other_work'
    WHERE id = $id
    ";

    $conn->query($update);

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
                                  <form method="POST" class="row g-3">

<div class="col-md-4">
    <label>Staff Name</label>
    <input type="text" class="form-control"
           value="<?= htmlspecialchars($data['name']) ?>" readonly>
</div>

<div class="col-md-4">
    <label>Work Date</label>
    <input type="date" name="work_date"
           value="<?= $data['work_date'] ?>"
           class="form-control" required>
</div>

<div class="col-md-4">
    <label>Total Calls</label>
    <input type="number" name="total_calls"
           value="<?= $data['total_calls'] ?>"
           class="form-control" required>
</div>

<div class="col-md-4">
    <label>Calls Connected</label>
    <input type="number" name="calls_connected"
           value="<?= $data['calls_connected'] ?>"
           class="form-control" required>
</div>

<div class="col-md-4">
    <label>Busy / Invalid Calls</label>
    <input type="number" name="busy_invalid_calls"
           value="<?= $data['busy_invalid_calls'] ?>"
           class="form-control" required>
</div>

<div class="col-md-4">
    <label>Follow-ups Done</label>
    <input type="number" name="followups_done"
           value="<?= $data['followups_done'] ?>"
           class="form-control" required>
</div>

<div class="col-md-4">
    <label>Working Hours</label>
    <input type="text" name="working_hours"
           value="<?= htmlspecialchars($data['working_hours']) ?>"
           class="form-control">
</div>

<div class="col-md-4">
    <label>Status</label>
    <select name="daily_status" class="form-select">
        <option <?= $data['daily_status']=='Excellent'?'selected':'' ?>>Excellent</option>
        <option <?= $data['daily_status']=='Good'?'selected':'' ?>>Good</option>
        <option <?= $data['daily_status']=='Average'?'selected':'' ?>>Average</option>
        <option <?= $data['daily_status']=='Needs Improvement'?'selected':'' ?>>Needs Improvement</option>
    </select>
</div>

<div class="col-12">
    <label>Other Work</label>
    <textarea name="other_work" class="form-control" rows="4"><?= htmlspecialchars($data['other_work']) ?></textarea>
</div>

<div class="col-12">
    <button type="submit" class="btn btn-success">Update</button>
    <a href="staff-work-list.php" class="btn btn-secondary">Cancel</a>
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