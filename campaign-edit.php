<?php
session_start();
include "db.php";
require 'access-control.php';

/* 🔐 Access Control */
if (!hasAccess('lead_source_tracking')) {
    http_response_code(403);
    die("Access Denied");
}
/* ===== AUTH CHECK ===== */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access Denied. Only admin can edit campaigns.");
}

/* ===== VALIDATE ID ===== */
if (!isset($_GET['id'])) {
    die("Invalid Campaign ID");
}

$campaignId = intval($_GET['id']);

/* ===== FETCH CAMPAIGN ===== */
$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
$stmt->bind_param("i", $campaignId);
$stmt->execute();
$result = $stmt->get_result();
$campaign = $result->fetch_assoc();

if (!$campaign) {
    die("Campaign not found");
}

/* ===== UPDATE LOGIC ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $platform       = $_POST['platform'];
    $campaign_name  = $_POST['campaign_name'];
    $lead_mode      = $_POST['lead_mode'];
    $generate_date  = $_POST['generate_date'];

    $update = $conn->prepare("
        UPDATE campaigns 
        SET platform = ?, 
            campaign_name = ?, 
            lead_mode = ?, 
            generate_date = ?
        WHERE id = ?
    ");

    $update->bind_param(
        "ssssi",
        $platform,
        $campaign_name,
        $lead_mode,
        $generate_date,
        $campaignId
    );

    if ($update->execute()) {
        header("Location: campaign-list.php?updated=1");
        exit;
    } else {
        $error = "Update failed.";
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
    <title>Add Campaign Data</title>
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
        <?php include "header.php"; ?>
        <!-- Page Header Ends -->
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
                    <h4>Edit Campaign</h4>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger m-3">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="card-body custom-input">
                    <form method="POST" class="form theme-form dark-inputs">
                        <div class="card-body">

                            <!-- Platform -->
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label">Platform</label>
                                        <select name="platform" class="form-select btn-pill digits" required>
                                            <?php
                                            $platforms = [
                                                "Website","Walk-in","Instagram","Facebook",
                                                "Google Ads","Facebook Ads","Reference",
                                                "Old Student Reference","Telecalling",
                                                "IVR","Just Dial","Other Platforms"
                                            ];
                                            foreach ($platforms as $p):
                                            ?>
                                                <option value="<?= $p ?>"
                                                    <?= $campaign['platform'] == $p ? 'selected' : '' ?>>
                                                    <?= $p ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Campaign Name -->
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label">Campaign Name</label>
                                        <input name="campaign_name"
                                               class="form-control btn-pill"
                                               type="text"
                                               value="<?= htmlspecialchars($campaign['campaign_name']) ?>"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <!-- Lead Mode -->
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label">Lead Mode</label>
                                        <select name="lead_mode" class="form-select btn-pill digits" required>
                                            <option value="Auto"
                                                <?= $campaign['lead_mode'] == 'Auto' ? 'selected' : '' ?>>
                                                Auto
                                            </option>
                                            <option value="Manual"
                                                <?= $campaign['lead_mode'] == 'Manual' ? 'selected' : '' ?>>
                                                Manual
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Generate Date -->
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label">Generate Date</label>
                                        <input name="generate_date"
                                               class="form-control btn-pill px-4"
                                               type="date"
                                               value="<?= $campaign['generate_date'] ?>"
                                               required>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer text-center">
                            <button class="btn btn-primary me-3" type="submit">
                                Update Campaign
                            </button>

                            <a href="campaign-delete.php?id=<?= $campaignId ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this campaign?');">
                                Delete Campaign
                            </a>

                            <a href="campaign-list.php" class="btn btn-light ms-2">
                                Cancel
                            </a>
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